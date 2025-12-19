<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * 空运费价格提供者
 *
 * 始终返回 null，让调用方使用默认运费逻辑
 */
#[AsAlias(id: FreightPriceProviderInterface::class)]
final class NullFreightPriceProvider implements FreightPriceProviderInterface
{
    public function findFreightPriceBySkus(string $freightId, array $skus): ?string
    {
        return null;
    }
}
