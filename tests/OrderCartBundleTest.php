<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\OrderCartBundle\OrderCartBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(OrderCartBundle::class)]
#[RunTestsInSeparateProcesses]
final class OrderCartBundleTest extends AbstractBundleTestCase
{
    public function testBundleCanBeRegisteredInContainer(): void
    {
        $container = new ContainerBuilder();
        $bundleClass = self::getBundleClass();
        $this->assertIsString($bundleClass, 'Bundle class should be a string');

        $bundle = new $bundleClass();
        self::assertInstanceOf(OrderCartBundle::class, $bundle);
        $this->assertInstanceOf(OrderCartBundle::class, $bundle);

        // Bundle 应该能够正确构建
        $bundle->build($container);

        // 验证 Bundle 名称
        $this->assertEquals('OrderCartBundle', $bundle->getName());
    }

    public function testBundleHasCorrectPath(): void
    {
        $bundleClass = self::getBundleClass();
        $this->assertIsString($bundleClass, 'Bundle class should be a string');

        $bundle = new $bundleClass();
        self::assertInstanceOf(OrderCartBundle::class, $bundle);
        $this->assertInstanceOf(OrderCartBundle::class, $bundle);

        $path = $bundle->getPath();

        $this->assertStringContainsString('order-cart-bundle', $path);
        $this->assertDirectoryExists($path);
    }
}
