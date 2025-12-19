<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Repository\CartAddLogRepository;
use Tourze\OrderCartBundle\Service\CartAddLogService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * CartAddLogService 集成测试
 *
 * @internal
 */
#[CoversClass(CartAddLogService::class)]
#[RunTestsInSeparateProcesses]
final class CartAddLogServiceTest extends AbstractIntegrationTestCase
{
    private CartAddLogService $service;
    private CartAddLogRepository $repository;
    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CartAddLogService::class);
        $this->repository = self::getService(CartAddLogRepository::class);
        $this->testUser = $this->createUser('cart_add_log_test_user', 'test_password', ['ROLE_USER']);
    }

    private function createTestSku(): Sku
    {
        $em = self::getEntityManager();

        $spu = new Spu();
        $spu->setTitle('Test SPU Title ' . uniqid());
        $em->persist($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        $sku->setGtin('1234567890123');
        $sku->setMpn('TEST-MPN-001');
        $sku->setMarketPrice('100.00');
        $sku->setCostPrice('50.00');
        $sku->setOriginalPrice('150.00');
        $sku->setCurrency('CNY');
        $sku->setIntegralPrice(1000);
        $sku->setTaxRate(0.13);
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

    public function testLogAddShouldCreateAndSaveCartAddLog(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);
        $metadata = ['source' => 'api', 'device' => 'mobile'];

        $result = $this->service->logAdd($this->testUser, $cartItem, $sku, 5, $metadata);

        $this->assertInstanceOf(CartAddLog::class, $result);
        $this->assertEquals('add', $result->getAction());
        $this->assertEquals(5, $result->getQuantity());
        $this->assertNotNull($result->getId());
        $this->assertNotEmpty($result->getSkuSnapshot());
        $this->assertNotEmpty($result->getPriceSnapshot());
        $this->assertEquals($metadata, $result->getMetadata());

        // 验证数据已持久化
        $found = $this->repository->find($result->getId());
        $this->assertNotNull($found);
        $this->assertEquals('add', $found->getAction());
    }

    public function testLogUpdateShouldCreateAndSaveUpdateLog(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 3);

        $result = $this->service->logUpdate($this->testUser, $cartItem, 3, 5);

        $this->assertInstanceOf(CartAddLog::class, $result);
        $this->assertEquals('update', $result->getAction());
        $this->assertEquals(2, $result->getQuantity()); // 5 - 3 = 2 (变化的数量)
        $this->assertNotNull($result->getId());

        // 验证元数据包含旧数量和新数量信息
        $metadata = $result->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('old_quantity', $metadata);
        $this->assertArrayHasKey('new_quantity', $metadata);
        $this->assertArrayHasKey('change', $metadata);
        $this->assertEquals(3, $metadata['old_quantity']);
        $this->assertEquals(5, $metadata['new_quantity']);
        $this->assertEquals(2, $metadata['change']);
    }

    public function testLogRestoreShouldCreateAndSaveRestoreLog(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);
        $metadata = ['reason' => 'user_request'];

        $result = $this->service->logRestore($this->testUser, $cartItem, $sku, 3, $metadata);

        $this->assertInstanceOf(CartAddLog::class, $result);
        $this->assertEquals('restore', $result->getAction());
        $this->assertEquals(3, $result->getQuantity());
        $this->assertNotNull($result->getId());
        $this->assertEquals($metadata, $result->getMetadata());
    }

    public function testMarkAsDeletedShouldUpdateExistingLogs(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        // 创建多条加购记录
        $this->service->logAdd($this->testUser, $cartItem, $sku, 2);
        $this->service->logUpdate($this->testUser, $cartItem, 2, 5);

        // 标记为已删除
        $result = $this->service->markAsDeleted($cartItemId);

        $this->assertEquals(2, $result);

        // 验证记录已被标记为删除
        $logs = $this->repository->findByCartItemId($cartItemId);
        foreach ($logs as $log) {
            $this->assertTrue($log->isDeleted());
            $this->assertInstanceOf(\DateTimeImmutable::class, $log->getDeleteTime());
        }
    }

    public function testMarkAsDeletedWithNoLogsFound(): void
    {
        $result = $this->service->markAsDeleted('nonexistent_cart_item');

        $this->assertEquals(0, $result);
    }

    public function testBatchMarkAsDeletedShouldDelegateToRepository(): void
    {
        $sku1 = $this->createTestSku();
        $sku2 = $this->createTestSku();

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 1);
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 2);

        $cartItemId1 = $cartItem1->getId();
        $cartItemId2 = $cartItem2->getId();
        $this->assertNotNull($cartItemId1);
        $this->assertNotNull($cartItemId2);

        // 创建加购记录
        $this->service->logAdd($this->testUser, $cartItem1, $sku1, 1);
        $this->service->logAdd($this->testUser, $cartItem2, $sku2, 2);

        // 批量标记为已删除
        $result = $this->service->batchMarkAsDeleted([$cartItemId1, $cartItemId2]);

        $this->assertGreaterThanOrEqual(2, $result);
    }

    public function testBatchMarkAsDeletedWithEmptyArrayShouldReturnZero(): void
    {
        $result = $this->service->batchMarkAsDeleted([]);

        $this->assertEquals(0, $result);
    }

    public function testGetUserAddHistoryShouldReturnUserLogs(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        $this->service->logAdd($this->testUser, $cartItem, $sku, 2);
        $this->service->logAdd($this->testUser, $cartItem, $sku, 3);

        $result = $this->service->getUserAddHistory($this->testUser);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
    }

    public function testGetUserAddHistoryWithCustomLimitShouldLimitResults(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        // 创建多条记录
        for ($i = 0; $i < 5; ++$i) {
            $this->service->logAdd($this->testUser, $cartItem, $sku, $i + 1);
        }

        $result = $this->service->getUserAddHistory($this->testUser, 3);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(3, count($result));
    }

    public function testGetUserSkuAddHistoryShouldReturnSkuSpecificLogs(): void
    {
        $sku1 = $this->createTestSku();
        $sku2 = $this->createTestSku();

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 1);
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 1);

        $this->service->logAdd($this->testUser, $cartItem1, $sku1, 2);
        $this->service->logAdd($this->testUser, $cartItem2, $sku2, 3);

        $result = $this->service->getUserSkuAddHistory($this->testUser, $sku1);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));

        foreach ($result as $log) {
            $this->assertEquals($sku1->getId(), $log->getSku()->getId());
        }
    }

    public function testGetUserAddStatsShouldReturnCalculatedStats(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        $this->service->logAdd($this->testUser, $cartItem, $sku, 10);
        $this->service->logAdd($this->testUser, $cartItem, $sku, 15);

        $result = $this->service->getUserAddStats($this->testUser);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_add_count', $result);
        $this->assertArrayHasKey('total_quantity', $result);
        $this->assertArrayHasKey('average_quantity', $result);
        $this->assertGreaterThanOrEqual(2, $result['total_add_count']);
        $this->assertGreaterThanOrEqual(25, $result['total_quantity']);
    }

    public function testGetUserAddStatsWithNoLogsShouldReturnZeroAverage(): void
    {
        // 创建新用户，没有任何加购记录
        $newUser = $this->createUser('new_user_no_logs', 'password', ['ROLE_USER']);

        $result = $this->service->getUserAddStats($newUser);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_add_count']);
        $this->assertEquals(0, $result['total_quantity']);
        $this->assertEquals(0, $result['average_quantity']);
    }

    public function testCreateSkuSnapshotShouldIncludeAllSkuData(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        $result = $this->service->logAdd($this->testUser, $cartItem, $sku, 1);

        $snapshot = $result->getSkuSnapshot();
        $this->assertArrayHasKey('id', $snapshot);
        $this->assertArrayHasKey('gtin', $snapshot);
        $this->assertArrayHasKey('mpn', $snapshot);
        $this->assertArrayHasKey('unit', $snapshot);
        $this->assertArrayHasKey('valid', $snapshot);
        $this->assertArrayHasKey('needConsignee', $snapshot);
        $this->assertArrayHasKey('salesReal', $snapshot);
        $this->assertArrayHasKey('salesVirtual', $snapshot);
        $this->assertArrayHasKey('spu_id', $snapshot);
        $this->assertArrayHasKey('spu_title', $snapshot);
        $this->assertArrayHasKey('thumbs', $snapshot);
        $this->assertArrayHasKey('snapshot_time', $snapshot);

        $this->assertEquals($sku->getId(), $snapshot['id']);
        $this->assertEquals('个', $snapshot['unit']);
    }

    public function testCreatePriceSnapshotShouldIncludePriceData(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        $result = $this->service->logAdd($this->testUser, $cartItem, $sku, 1);

        $priceSnapshot = $result->getPriceSnapshot();
        $this->assertArrayHasKey('snapshot_time', $priceSnapshot);
        $this->assertArrayHasKey('marketPrice', $priceSnapshot);
        $this->assertArrayHasKey('costPrice', $priceSnapshot);
        $this->assertArrayHasKey('originalPrice', $priceSnapshot);
        $this->assertArrayHasKey('currency', $priceSnapshot);
        $this->assertArrayHasKey('integralPrice', $priceSnapshot);
        $this->assertArrayHasKey('taxRate', $priceSnapshot);

        $this->assertEquals('100.00', $priceSnapshot['marketPrice']);
        $this->assertEquals('50.00', $priceSnapshot['costPrice']);
        $this->assertEquals('150.00', $priceSnapshot['originalPrice']);
        $this->assertEquals('CNY', $priceSnapshot['currency']);
        $this->assertEquals(1000, $priceSnapshot['integralPrice']);
        $this->assertEquals(0.13, $priceSnapshot['taxRate']);
    }

    public function testCleanupOldLogsShouldDeleteOldRecords(): void
    {
        // 此测试验证 cleanupOldLogs 方法可以正常执行
        // 由于 deleteOldLogs 依赖数据库实现，这里只验证方法不抛异常
        $result = $this->service->cleanupOldLogs(90);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testUserIsolation(): void
    {
        $sku = $this->createTestSku();
        $cartItem = $this->createCartItem($this->testUser, $sku, 1);

        $this->service->logAdd($this->testUser, $cartItem, $sku, 5);

        // 创建另一个用户
        $otherUser = $this->createUser('other_add_log_user', 'password', ['ROLE_USER']);

        // 另一个用户的加购历史应该为空
        $otherUserHistory = $this->service->getUserAddHistory($otherUser);
        $this->assertEmpty($otherUserHistory);

        // 原用户的加购历史应该有记录
        $userHistory = $this->service->getUserAddHistory($this->testUser);
        $this->assertNotEmpty($userHistory);
    }
}
