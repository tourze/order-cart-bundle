<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderCartBundle\Service\FreightPriceProviderInterface;
use Tourze\OrderCartBundle\Service\NullFreightPriceProvider;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;

#[CoversClass(NullFreightPriceProvider::class)]
#[RunTestsInSeparateProcesses]
final class NullFreightPriceProviderTest extends AbstractIntegrationTestCase
{
    private NullFreightPriceProvider $provider;

    protected function onSetUp(): void
    {
        $this->provider = self::getService(NullFreightPriceProvider::class);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(FreightPriceProviderInterface::class, $this->provider);
    }

    public function testServiceCanBeRetrievedByInterface(): void
    {
        $provider = self::getService(FreightPriceProviderInterface::class);
        $this->assertInstanceOf(NullFreightPriceProvider::class, $provider);
    }

    public function testFindFreightPriceBySkusReturnsNull(): void
    {
        $result = $this->provider->findFreightPriceBySkus('freight-123', []);

        $this->assertNull($result);
    }

    public function testFindFreightPriceBySkusReturnsNullWithSkus(): void
    {
        $mockSku = $this->createMock(Sku::class);

        $result = $this->provider->findFreightPriceBySkus('freight-456', [$mockSku]);

        $this->assertNull($result);
    }
}
