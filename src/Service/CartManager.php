<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\LockServiceBundle\Service\LockService;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Event\CartClearedEvent;
use Tourze\OrderCartBundle\Event\CartItemAddedEvent;
use Tourze\OrderCartBundle\Event\CartItemRemovedEvent;
use Tourze\OrderCartBundle\Event\CartItemUpdatedEvent;
use Tourze\OrderCartBundle\Event\CartSelectionChangedEvent;
use Tourze\OrderCartBundle\Exception\CartItemNotFoundException;
use Tourze\OrderCartBundle\Exception\CartLimitExceededException;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\CartAddLogService;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductServiceContracts\SkuLoaderInterface;
use Tourze\StockManageBundle\Service\StockServiceInterface;

#[Autoconfigure(public: true)]
#[AsAlias(id: CartManagerInterface::class)]
final class CartManager implements CartManagerInterface
{
    private const MAX_CART_ITEMS = 100;
    private const MAX_QUANTITY_PER_ITEM = 999;

    public function __construct(
        private readonly CartItemRepository $repository,
        private readonly SkuLoaderInterface $skuLoader,
        private readonly StockServiceInterface $stockService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LockService $lockService,
        private readonly CartAddLogService $cartAddLogService,
    ) {
    }

    public function addItem(UserInterface $user, Sku $sku, int $quantity, array $metadata = []): CartItem
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException('新增数量必须大于0');
        }

        if ($quantity > self::MAX_QUANTITY_PER_ITEM) {
            throw new InvalidQuantityException(sprintf('超出最大购买数量 %d', self::MAX_QUANTITY_PER_ITEM));
        }

        // Validate SKU exists and is valid
        $validSku = $this->skuLoader->loadSkuByIdentifier($sku->getId());
        if (null === $validSku) {
            $spuTitle = $sku->getSpu()?->getTitle() ?? '未知商品';
            throw new InvalidSkuException(sprintf('商品已下架: %s', $spuTitle));
        }

        // Check stock availability
        $stockSummary = $this->stockService->getAvailableStock($sku);
        if ($stockSummary->getAvailableQuantity() < $quantity) {
            throw new InvalidSkuException('库存不足');
        }

        $currentItemCount = $this->repository->countByUser($user);
        if ($currentItemCount >= self::MAX_CART_ITEMS) {
            throw new CartLimitExceededException(sprintf('购物车不能加购超过 %d 个商品', self::MAX_CART_ITEMS));
        }

        $existingItem = $this->repository->findByUserAndSku($user, $sku);
        if (null !== $existingItem) {
            $newQuantity = $existingItem->getQuantity() + $quantity;
            if ($newQuantity > self::MAX_QUANTITY_PER_ITEM) {
                throw new InvalidQuantityException(sprintf('总数不能超过 %d', self::MAX_QUANTITY_PER_ITEM));
            }

            $oldQuantity = $existingItem->getQuantity();
            $existingItem->setQuantity($newQuantity);
            $existingItem->setMetadata(array_merge($existingItem->getMetadata(), $metadata));
            $existingItem->setUpdateTime(new \DateTimeImmutable());

            $this->repository->save($existingItem);

            // 记录更新操作
            $this->cartAddLogService->logUpdate($user, $existingItem, $oldQuantity, $newQuantity);

            $this->eventDispatcher->dispatch(new CartItemUpdatedEvent(
                $user,
                $existingItem,
                $oldQuantity,
                $newQuantity
            ));

            return $existingItem;
        }

        $cartItem = new CartItem();
        $cartItem->setUser($user);
        $cartItem->setSku($sku);
        $cartItem->setQuantity($quantity);
        $cartItem->setSelected(true);
        $cartItem->setMetadata($metadata);
        $cartItem->setCreateTime(new \DateTimeImmutable());
        $cartItem->setUpdateTime(new \DateTimeImmutable());

        $this->repository->save($cartItem);

        // 记录加购操作
        $this->cartAddLogService->logAdd($user, $cartItem, $sku, $quantity, $metadata);

        $this->eventDispatcher->dispatch(new CartItemAddedEvent($user, $cartItem));

        return $cartItem;
    }

    public function updateQuantity(UserInterface $user, string $cartItemId, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException('数量不能小于0');
        }

        if ($quantity > self::MAX_QUANTITY_PER_ITEM) {
            throw new InvalidQuantityException(sprintf('最多添加数量为 %d', self::MAX_QUANTITY_PER_ITEM));
        }

        $lockKey = sprintf('cart_update_%s_%s', $user->getUserIdentifier(), $cartItemId);

        $result = $this->lockService->blockingRun($lockKey, function () use ($user, $cartItemId, $quantity): CartItem {
            $cartItem = $this->repository->findByUserAndId($user, $cartItemId);
            if (null === $cartItem) {
                throw new CartItemNotFoundException('购物车不存在');
            }

            $oldQuantity = $cartItem->getQuantity();
            $cartItem->setQuantity($quantity);
            $cartItem->setUpdateTime(new \DateTimeImmutable());

            $this->repository->save($cartItem);

            // 记录数量更新操作
            $this->cartAddLogService->logUpdate($user, $cartItem, $oldQuantity, $quantity);

            $this->eventDispatcher->dispatch(new CartItemUpdatedEvent($user, $cartItem, $oldQuantity, $quantity));

            return $cartItem;
        });

        assert($result instanceof CartItem);

        return $result;
    }

    public function removeItem(UserInterface $user, string $cartItemId): void
    {
        $cartItem = $this->repository->findByUserAndId($user, $cartItemId);
        if (null === $cartItem) {
            throw new CartItemNotFoundException(sprintf('Cart item %d not found', $cartItemId));
        }

        $skuId = $cartItem->getSku()->getId();

        // 先标记相关日志为已删除
        $this->cartAddLogService->markAsDeleted($cartItemId);

        $this->repository->remove($cartItem);

        $this->eventDispatcher->dispatch(new CartItemRemovedEvent($user, $cartItemId, $skuId));
    }

    public function clearCart(UserInterface $user): int
    {
        $cartItems = $this->repository->findByUser($user);
        $count = count($cartItems);

        if ($count > 0) {
            // 收集所有购物车项ID
            $cartItemIds = [];
            foreach ($cartItems as $cartItem) {
                $id = $cartItem->getId();
                if (null !== $id) {
                    $cartItemIds[] = $id;
                }
            }

            // 批量标记日志为已删除
            $this->cartAddLogService->batchMarkAsDeleted($cartItemIds);

            // 删除所有购物车项
            foreach ($cartItems as $cartItem) {
                $this->repository->remove($cartItem);
            }

            $this->eventDispatcher->dispatch(new CartClearedEvent($user, $count));
        }

        return $count;
    }

    public function updateSelection(UserInterface $user, string $cartItemId, bool $selected): CartItem
    {
        $cartItem = $this->repository->findByUserAndId($user, $cartItemId);
        if (null === $cartItem) {
            throw new CartItemNotFoundException(sprintf('Cart item %d not found', $cartItemId));
        }

        $cartItem->setSelected($selected);
        $cartItem->setUpdateTime(new \DateTimeImmutable());

        $this->repository->save($cartItem);

        $this->eventDispatcher->dispatch(new CartSelectionChangedEvent($user, $cartItem, $selected));

        return $cartItem;
    }

    public function batchUpdateSelection(UserInterface $user, array $cartItemIds, bool $selected): array
    {
        if ([] === $cartItemIds) {
            return [];
        }

        $cartItems = $this->repository->findByUserAndIds($user, $cartItemIds);
        $results = [];

        foreach ($cartItems as $cartItem) {
            $cartItem->setSelected($selected);
            $cartItem->setUpdateTime(new \DateTimeImmutable());

            $this->repository->save($cartItem);

            $this->eventDispatcher->dispatch(new CartSelectionChangedEvent($user, $cartItem, $selected));

            $id = $cartItem->getId();
            if (null !== $id) {
                $results[$id] = $cartItem;
            }
        }

        return $results;
    }

    public function getCartItemCount(UserInterface $user): int
    {
        return $this->repository->countByUser($user);
    }

    public function getCartTotalQuantity(UserInterface $user): int
    {
        return $this->repository->getTotalQuantityByUser($user);
    }
}
