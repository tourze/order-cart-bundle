<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Repository\CartAddLogRepository;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Service\PriceService;

/**
 * 购物车加购日志服务
 * 负责记录用户所有的购物车相关操作
 */
#[Autoconfigure(public: true)]
class CartAddLogService
{
    public function __construct(
        private readonly CartAddLogRepository $repository,
        private readonly PriceService $priceService,
    ) {
    }

    /**
     * 记录加购操作
     *
     * @param array<string, mixed> $metadata
     */
    public function logAdd(UserInterface $user, CartItem $cartItem, Sku $sku, int $quantity, array $metadata = []): CartAddLog
    {
        $log = new CartAddLog();
        $log->setUser($user);
        $log->setSku($sku);
        $log->setCartItemId($cartItem->getId());
        $log->setQuantity($quantity);
        $log->setAction('add');
        $log->setSkuSnapshot($this->createSkuSnapshot($sku));
        $log->setPriceSnapshot($this->createPriceSnapshot($sku));
        $log->setMetadata($metadata);

        $this->repository->save($log);

        return $log;
    }

    /**
     * 记录更新操作
     */
    public function logUpdate(UserInterface $user, CartItem $cartItem, int $oldQuantity, int $newQuantity): CartAddLog
    {
        $log = new CartAddLog();
        $log->setUser($user);
        $log->setSku($cartItem->getSku());
        $log->setCartItemId($cartItem->getId());
        $log->setQuantity($newQuantity - $oldQuantity); // 记录变化的数量
        $log->setAction('update');
        $log->setSkuSnapshot($this->createSkuSnapshot($cartItem->getSku()));
        $log->setPriceSnapshot($this->createPriceSnapshot($cartItem->getSku()));
        $log->setMetadata([
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'change' => $newQuantity - $oldQuantity,
        ]);

        $this->repository->save($log);

        return $log;
    }

    /**
     * 记录恢复操作（当重新添加已删除的商品时）
     *
     * @param array<string, mixed> $metadata
     */
    public function logRestore(UserInterface $user, CartItem $cartItem, Sku $sku, int $quantity, array $metadata = []): CartAddLog
    {
        $log = new CartAddLog();
        $log->setUser($user);
        $log->setSku($sku);
        $log->setCartItemId($cartItem->getId());
        $log->setQuantity($quantity);
        $log->setAction('restore');
        $log->setSkuSnapshot($this->createSkuSnapshot($sku));
        $log->setPriceSnapshot($this->createPriceSnapshot($sku));
        $log->setMetadata($metadata);

        $this->repository->save($log);

        return $log;
    }

    /**
     * 标记指定购物车项的加购记录为已删除
     */
    public function markAsDeleted(string $cartItemId): int
    {
        $logs = $this->repository->findByCartItemId($cartItemId);
        $count = 0;

        foreach ($logs as $log) {
            if (!$log->isDeleted()) {
                $log->markAsDeleted();
                ++$count;
            }
        }

        if ($count > 0) {
            // 批量保存
            foreach ($logs as $log) {
                if ($log->isDeleted()) {
                    $this->repository->save($log, false);
                }
            }
            if ([] !== $logs) {
                $this->repository->save($logs[0], true); // 触发flush
            }
        }

        return $count;
    }

    /**
     * 批量标记购物车项的加购记录为已删除
     *
     * @param array<string> $cartItemIds
     */
    public function batchMarkAsDeleted(array $cartItemIds): int
    {
        if ([] === $cartItemIds) {
            return 0;
        }

        return $this->repository->markAsDeletedByCartItemIds($cartItemIds);
    }

    /**
     * 获取用户的加购历史
     *
     * @return array<CartAddLog>
     */
    public function getUserAddHistory(UserInterface $user, int $limit = 100): array
    {
        return $this->repository->findByUser($user, $limit);
    }

    /**
     * 获取用户对指定SKU的加购历史
     *
     * @return array<CartAddLog>
     */
    public function getUserSkuAddHistory(UserInterface $user, Sku $sku): array
    {
        return $this->repository->findByUserAndSku($user, $sku);
    }

    /**
     * 获取用户加购统计信息
     *
     * @return array<string, mixed>
     */
    public function getUserAddStats(UserInterface $user): array
    {
        $totalAddCount = $this->repository->countByUser($user);
        $totalQuantity = $this->repository->sumQuantityByUser($user);

        return [
            'total_add_count' => $totalAddCount,
            'total_quantity' => $totalQuantity,
            'average_quantity' => $totalAddCount > 0 ? round($totalQuantity / $totalAddCount, 2) : 0,
        ];
    }

    /**
     * 创建商品快照
     *
     * @return array<string, mixed>
     */
    private function createSkuSnapshot(Sku $sku): array
    {
        return [
            'id' => $sku->getId(),
            'gtin' => $sku->getGtin(),
            'mpn' => $sku->getMpn(),
            'unit' => $sku->getUnit(),
            'valid' => $sku->isValid(),
            'needConsignee' => $sku->isNeedConsignee(),
            'salesReal' => $sku->getSalesReal(),
            'salesVirtual' => $sku->getSalesVirtual(),
            'spu_id' => $sku->getSpu()?->getId(),
            'spu_title' => $sku->getSpu()?->getTitle(),
            'thumbs' => $sku->getThumbs(),
            'snapshot_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 创建价格快照
     *
     * @return array<string, mixed>
     */
    private function createPriceSnapshot(Sku $sku): array
    {
        // 获取SKU相关的价格记录
        $prices = $this->priceService->getPricesBySku($sku);
        $priceData = [];

        foreach ($prices as $price) {
            $priceData[] = [
                'id' => $price->getId(),
                'type' => $price->getType()->value,
                'currency' => $price->getCurrency(),
                'price' => $price->getPrice(),
                'taxRate' => $price->getTaxRate(),
                'priority' => $price->getPriority(),
                'effectTime' => $price->getEffectTime()?->format('Y-m-d H:i:s'),
                'expireTime' => $price->getExpireTime()?->format('Y-m-d H:i:s'),
                'canRefund' => $price->isCanRefund(),
            ];
        }

        return [
            'marketPrice' => $sku->getMarketPrice(),
            'costPrice' => $sku->getCostPrice(),
            'originalPrice' => $sku->getOriginalPrice(),
            'prices' => $priceData,
            'snapshot_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 清理旧的加购记录
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysToKeep} days");

        return $this->repository->deleteOldLogs($cutoffDate);
    }
}
