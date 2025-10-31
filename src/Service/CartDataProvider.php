<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\GeneralCartException;
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\StockManageBundle\Service\StockServiceInterface;

#[AsAlias(id: CartDataProviderInterface::class, public: true)]
final readonly class CartDataProvider implements CartDataProviderInterface
{
    public function __construct(
        private CartItemRepository $repository,
        private StockServiceInterface $stockService,
    ) {
    }

    public function getCartSummary(UserInterface $user): CartSummaryDTO
    {
        $cartItems = $this->repository->findByUser($user);

        if ([] === $cartItems) {
            return new CartSummaryDTO(0, 0, '0.00', '0.00');
        }

        $totalItems = count($cartItems);
        $selectedItems = 0;
        $selectedAmount = '0.00';
        $totalAmount = '0.00';

        foreach ($cartItems as $cartItem) {
            $sku = $cartItem->getSku();
            $marketPrice = $sku->getMarketPrice();

            if (null === $marketPrice) {
                continue;
            }

            $unitPrice = sprintf('%.2f', $marketPrice);
            $itemAmount = bcmul($unitPrice, (string) $cartItem->getQuantity(), 2);
            $totalAmount = bcadd($totalAmount, $itemAmount, 2);

            if ($cartItem->isSelected()) {
                ++$selectedItems;
                $selectedAmount = bcadd($selectedAmount, $itemAmount, 2);
            }
        }

        return new CartSummaryDTO($totalItems, $selectedItems, $totalAmount, $selectedAmount);
    }

    public function getCartItems(UserInterface $user): array
    {
        $cartItems = $this->repository->findByUser($user);

        if ([] === $cartItems) {
            return [];
        }

        return $this->convertCartItemsToDTOs($cartItems);
    }

    public function getSelectedItems(UserInterface $user): array
    {
        $cartItems = $this->repository->findSelectedByUser($user);

        if ([] === $cartItems) {
            return [];
        }

        return $this->convertCartItemsToDTOs($cartItems);
    }

    public function getItemCount(UserInterface $user): int
    {
        return $this->repository->countByUser($user);
    }

    public function getItemById(UserInterface $user, string $cartItemId): ?CartItemDTO
    {
        $cartItem = $this->repository->findByUserAndId($user, $cartItemId);

        if (null === $cartItem) {
            return null;
        }

        $product = $this->convertSkuToProductDTO($cartItem->getSku());

        if (null === $product) {
            throw new GeneralCartException(sprintf('Product not found for SKU %s', $cartItem->getSku()->getId()));
        }

        $id = $cartItem->getId();
        if (null === $id) {
            throw new GeneralCartException('Cart item has no ID');
        }

        return new CartItemDTO(
            $id,
            $product,
            $cartItem->getQuantity(),
            $cartItem->isSelected(),
            $cartItem->getMetadata(),
            $cartItem->getCreateTime(),
            $cartItem->getUpdateTime()
        );
    }

    /**
     * @param array<CartItem> $cartItems
     * @return array<int, CartItemDTO>
     */
    private function convertCartItemsToDTOs(array $cartItems): array
    {
        if ([] === $cartItems) {
            return [];
        }

        $dtos = [];
        foreach ($cartItems as $index => $cartItem) {
            $product = $this->convertSkuToProductDTO($cartItem->getSku());

            if (null === $product) {
                continue;
            }

            $id = $cartItem->getId();
            if (null === $id) {
                continue;
            }

            $dtos[$index] = new CartItemDTO(
                $id,
                $product,
                $cartItem->getQuantity(),
                $cartItem->isSelected(),
                $cartItem->getMetadata(),
                $cartItem->getCreateTime(),
                $cartItem->getUpdateTime()
            );
        }

        return array_values($dtos);
    }

    public function getSelectedCartEntities(UserInterface $user): array
    {
        return $this->repository->findSelectedByUser($user);
    }

    /**
     * 将SKU实体转换为ProductDTO
     */
    private function convertSkuToProductDTO(Sku $sku): ?ProductDTO
    {
        $marketPrice = $sku->getMarketPrice();
        if (null === $marketPrice) {
            return null;
        }

        // Get stock from stock service
        $stockSummary = $this->stockService->getAvailableStock($sku);
        $stock = $stockSummary->getAvailableQuantity();

        return new ProductDTO(
            skuId: $sku->getId(),
            name: $sku->getFullName(),
            price: sprintf('%.2f', $marketPrice),
            stock: $stock,
            isActive: $sku->getSpu()?->isValid() ?? true,
            mainThumb: $sku->getMainThumb(),
            attributes: []
        );
    }
}
