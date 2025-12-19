<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Service;

use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * 运费价格提供者接口
 *
 * 用于解耦购物车 bundle 与订单核心 bundle 的运费计算逻辑
 */
interface FreightPriceProviderInterface
{
    /**
     * 根据运费ID和SKU列表查找运费价格
     *
     * @param string $freightId 运费模板ID
     * @param array<Sku> $skus SKU列表
     * @return string|null 运费金额字符串，null 表示使用默认运费
     */
    public function findFreightPriceBySkus(string $freightId, array $skus): ?string;
}
