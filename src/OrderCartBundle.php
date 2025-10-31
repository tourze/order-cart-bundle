<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\JsonRPCSecurityBundle\JsonRPCSecurityBundle;
use Tourze\ProductCoreBundle\ProductCoreBundle;
use Tourze\StockManageBundle\StockManageBundle;

class OrderCartBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            ProductCoreBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            StockManageBundle::class => ['all' => true],
            JsonRPCSecurityBundle::class => ['all' => true],
        ];
    }
}
