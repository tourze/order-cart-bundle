<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Service\CartCacheService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(CartCacheService::class)]
#[RunTestsInSeparateProcesses]
final class CartCacheServiceTest extends AbstractIntegrationTestCase
{
    private CartCacheService $service;

    private UserInterface $user;

    protected function onSetUp(): void
    {
        $this->user = $this->createMock(UserInterface::class);
        $this->user->method('getUserIdentifier')->willReturn('testuser@example.com');

        // Get service from container for integration test
        $this->service = self::getService(CartCacheService::class);
    }

    public function testClearUserCartCacheShouldExecuteWithoutError(): void
    {
        // This is an integration test - we just verify it executes without throwing
        $this->expectNotToPerformAssertions();
        $this->service->clearUserCartCache($this->user);
    }

    public function testClearSkuRelatedCacheShouldExecuteWithoutError(): void
    {
        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('sku123');

        $this->expectNotToPerformAssertions();
        $this->service->clearSkuRelatedCache($sku);
    }

    public function testClearSpecificCartCacheShouldExecuteWithoutError(): void
    {
        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getId')->willReturn('item123');

        $this->expectNotToPerformAssertions();
        $this->service->clearSpecificCartCache($this->user, [$cartItem]);
    }

    public function testGenerateCacheTags(): void
    {
        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('sku123');

        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getSku')->willReturn($sku);

        $tags = $this->service->generateCacheTags($this->user, [$cartItem]);

        $this->assertContains('user_cart_testuser@example.com', $tags);
        $this->assertContains('sku_sku123', $tags);
    }

    public function testClearAllCartCacheShouldExecuteWithoutError(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->clearAllCartCache();
    }

    public function testGetCacheStats(): void
    {
        $stats = $this->service->getCacheStats();

        $this->assertArrayHasKey('cache_type', $stats);
        $this->assertArrayHasKey('supports_tags', $stats);
        $this->assertArrayHasKey('supports_delete', $stats);
        $this->assertIsBool($stats['supports_tags']);
        $this->assertIsBool($stats['supports_delete']);
    }
}
