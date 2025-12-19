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
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * CartCacheService 集成测试
 *
 * @internal
 */
#[CoversClass(CartCacheService::class)]
#[RunTestsInSeparateProcesses]
final class CartCacheServiceTest extends AbstractIntegrationTestCase
{
    private CartCacheService $service;
    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CartCacheService::class);
        $this->testUser = $this->createUser('cart_cache_test_user', 'test_password', ['ROLE_USER']);
    }

    private function createTestSku(): Sku
    {
        $em = self::getEntityManager();

        $spu = new Spu();
        $spu->setTitle('Test Product ' . uniqid());
        $em->persist($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        $sku->setMarketPrice('99.99');
        $em->persist($sku);

        $em->flush();

        return $sku;
    }

    private function createCartItem(UserInterface $user, Sku $sku, int $quantity = 1): CartItem
    {
        $em = self::getEntityManager();

        $cartItem = new CartItem();
        $cartItem->setUser($user);
        $cartItem->setSku($sku);
        $cartItem->setQuantity($quantity);
        $cartItem->setSelected(true);
        $em->persist($cartItem);
        $em->flush();

        return $cartItem;
    }

    public function testClearUserCartCacheShouldExecuteWithoutError(): void
    {
        // 集成测试 - 验证方法可以正常执行而不抛出异常
        $this->expectNotToPerformAssertions();
        $this->service->clearUserCartCache($this->testUser);
    }

    public function testClearSkuRelatedCacheShouldExecuteWithoutError(): void
    {
        $sku = $this->createTestSku();

        $this->expectNotToPerformAssertions();
        $this->service->clearSkuRelatedCache($sku);
    }

    public function testClearSpecificCartCacheShouldExecuteWithoutError(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 2);

        $this->expectNotToPerformAssertions();
        $this->service->clearSpecificCartCache($this->testUser, [$cartItem]);
    }

    public function testClearSpecificCartCacheWithFreightId(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 2);

        $this->expectNotToPerformAssertions();
        $this->service->clearSpecificCartCache($this->testUser, [$cartItem], 'freight_123');
    }

    public function testGenerateCacheTags(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 2);

        $tags = $this->service->generateCacheTags($this->testUser, [$cartItem]);

        $this->assertIsArray($tags);
        $this->assertContains('user_cart_' . $this->testUser->getUserIdentifier(), $tags);
        $this->assertContains('sku_' . $sku->getId(), $tags);
    }

    public function testGenerateCacheTagsWithMultipleItems(): void
    {
        $sku1 = $this->createTestSku();
        $sku2 = $this->createTestSku();

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 1);
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 2);

        $tags = $this->service->generateCacheTags($this->testUser, [$cartItem1, $cartItem2]);

        $this->assertIsArray($tags);
        $this->assertContains('user_cart_' . $this->testUser->getUserIdentifier(), $tags);
        $this->assertContains('sku_' . $sku1->getId(), $tags);
        $this->assertContains('sku_' . $sku2->getId(), $tags);
        // 验证没有重复的标签
        $this->assertEquals(count($tags), count(array_unique($tags)));
    }

    public function testGenerateCacheTagsDeduplication(): void
    {
        $sku = $this->createTestSku();

        // 创建一个购物车项，然后传入两次（模拟同一个 SKU 出现两次的场景）
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        $tags = $this->service->generateCacheTags($this->testUser, [$cartItem, $cartItem]);

        // 应该只有一个用户标签和一个 SKU 标签（去重后）
        $this->assertCount(2, $tags);
    }

    public function testClearAllCartCacheShouldExecuteWithoutError(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->clearAllCartCache();
    }

    public function testGetCacheStats(): void
    {
        $stats = $this->service->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_type', $stats);
        $this->assertArrayHasKey('supports_tags', $stats);
        $this->assertArrayHasKey('supports_delete', $stats);
        $this->assertIsBool($stats['supports_tags']);
        $this->assertIsBool($stats['supports_delete']);
    }

    public function testClearUserCartCacheForDifferentUsers(): void
    {
        $otherUser = $this->createUser('other_cache_user', 'password', ['ROLE_USER']);

        // 验证可以为不同用户清除缓存而不互相影响
        $this->expectNotToPerformAssertions();
        $this->service->clearUserCartCache($this->testUser);
        $this->service->clearUserCartCache($otherUser);
    }

    public function testClearSpecificCartCacheWithEmptyCartItems(): void
    {
        // 验证空购物车项数组不会导致异常
        $this->expectNotToPerformAssertions();
        $this->service->clearSpecificCartCache($this->testUser, []);
    }

    public function testGenerateCacheTagsWithEmptyCartItems(): void
    {
        $tags = $this->service->generateCacheTags($this->testUser, []);

        $this->assertIsArray($tags);
        // 至少应该包含用户标签
        $this->assertContains('user_cart_' . $this->testUser->getUserIdentifier(), $tags);
    }
}
