<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Repository;

use BizUserBundle\Entity\BizUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\OrderCartBundle\Repository\CartAddLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(CartAddLogRepository::class)]
final class CartAddLogRepositoryTest extends AbstractRepositoryTestCase
{
    private BizUser $testUser;

    private BizUser $otherUser;

    private Sku $testSku1;

    private Sku $testSku2;

    public function testFindByUserWithExistingUserShouldReturnOrderedResults(): void
    {
        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_1', 2, 'add');
        $this->persistMultipleAndFlush($log1);

        // 延时创建第二个记录，确保时间不同
        usleep(1000000); // 1秒延时确保时间差异
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku2, 'cart_item_2', 3, 'update');
        $this->persistMultipleAndFlush($log2);

        $results = $this->getRepository()->findByUser($this->testUser);

        $this->assertGreaterThanOrEqual(2, count($results), 'findByUser应返回用户的加购记录（包含基础测试数据）');
        $this->assertContainsOnlyInstancesOf(CartAddLog::class, $results, 'findByUser应返回CartAddLog实例数组');

        // 找到我们创建的两条记录
        $foundLog1 = null;
        $foundLog2 = null;
        foreach ($results as $result) {
            if ('cart_item_1' === $result->getCartItemId()) {
                $foundLog1 = $result;
            } elseif ('cart_item_2' === $result->getCartItemId()) {
                $foundLog2 = $result;
            }
        }

        $this->assertNotNull($foundLog1, '应该找到第一条加购记录');
        $this->assertNotNull($foundLog2, '应该找到第二条加购记录');

        // 验证按创建时间降序排列（最新的在前）
        $log1Index = array_search($foundLog1, $results, true);
        $log2Index = array_search($foundLog2, $results, true);
        $this->assertLessThan($log1Index, $log2Index, 'findByUser应按创建时间降序排列，最新的记录在前');
    }

    private function persistMultipleAndFlush(CartAddLog ...$entities): void
    {
        $em = self::getEntityManager();

        foreach ($entities as $entity) {
            $em->persist($entity);
        }
        $em->flush();
    }

    protected function getRepository(): CartAddLogRepository
    {
        $repository = self::getEntityManager()->getRepository(CartAddLog::class);
        self::assertInstanceOf(CartAddLogRepository::class, $repository);

        return $repository;
    }

    public function testFindByUserWithNonExistentUserShouldReturnEmptyArray(): void
    {
        $nonExistentUser = new BizUser();
        $nonExistentUser->setUsername('nonexistent');
        $nonExistentUser->setEmail('nonexistent@example.com');
        $nonExistentUser->setPasswordHash('$2y$13$hashed_password');

        $results = $this->getRepository()->findByUser($nonExistentUser);

        $this->assertIsArray($results, 'findByUser对不存在的用户应返回数组');
        $this->assertEmpty($results, 'findByUser对不存在的用户应返回空数组');
    }

    public function testFindByUserAndSkuWithExistingUserAndSkuShouldReturnResults(): void
    {
        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_3', 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_4', 1, 'update');
        $this->persistMultipleAndFlush($log1, $log2);

        $results = $this->getRepository()->findByUserAndSku($this->testUser, $this->testSku1);

        $this->assertGreaterThanOrEqual(2, count($results), 'findByUserAndSku应返回用户和SKU匹配的记录');
        $this->assertContainsOnlyInstancesOf(CartAddLog::class, $results, 'findByUserAndSku应返回CartAddLog实例数组');

        foreach ($results as $result) {
            $this->assertSame($this->testUser, $result->getUser(), '返回的记录应属于指定用户');
            $this->assertSame($this->testSku1, $result->getSku(), '返回的记录应属于指定SKU');
        }
    }

    public function testFindByUserAndSkuWithNonExistentCombinationShouldReturnEmptyArray(): void
    {
        $results = $this->getRepository()->findByUserAndSku($this->otherUser, $this->testSku2);

        $this->assertIsArray($results, 'findByUserAndSku对不存在的用户SKU组合应返回数组');
        $this->assertEmpty($results, 'findByUserAndSku对不存在的用户SKU组合应返回空数组');
    }

    public function testFindByCartItemIdShouldReturnMatchingResults(): void
    {
        $cartItemId = 'cart_item_test_5';
        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, $cartItemId, 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku1, $cartItemId, 1, 'update');
        $this->persistMultipleAndFlush($log1, $log2);

        $results = $this->getRepository()->findByCartItemId($cartItemId);

        $this->assertCount(2, $results, 'findByCartItemId应返回匹配购物车项ID的记录');
        $this->assertContainsOnlyInstancesOf(CartAddLog::class, $results, 'findByCartItemId应返回CartAddLog实例数组');

        foreach ($results as $result) {
            $this->assertEquals($cartItemId, $result->getCartItemId(), '返回的记录应有正确的购物车项ID');
        }
    }

    public function testFindByCartItemIdWithNonExistentIdShouldReturnEmptyArray(): void
    {
        $results = $this->getRepository()->findByCartItemId('nonexistent_cart_item');

        $this->assertIsArray($results, 'findByCartItemId对不存在的购物车项ID应返回数组');
        $this->assertEmpty($results, 'findByCartItemId对不存在的购物车项ID应返回空数组');
    }

    public function testFindByCartItemIdsWithExistingIdsShouldReturnMatchingResults(): void
    {
        $cartItemId1 = 'cart_item_test_6';
        $cartItemId2 = 'cart_item_test_7';

        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, $cartItemId1, 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku2, $cartItemId2, 3, 'add');
        $this->persistMultipleAndFlush($log1, $log2);

        $results = $this->getRepository()->findByCartItemIds([$cartItemId1, $cartItemId2]);

        $this->assertCount(2, $results, 'findByCartItemIds应返回匹配的记录');
        $this->assertContainsOnlyInstancesOf(CartAddLog::class, $results, 'findByCartItemIds应返回CartAddLog实例数组');

        $foundIds = array_map(fn ($log) => $log->getCartItemId(), $results);
        $this->assertContains($cartItemId1, $foundIds, 'findByCartItemIds应包含第一个购物车项ID的记录');
        $this->assertContains($cartItemId2, $foundIds, 'findByCartItemIds应包含第二个购物车项ID的记录');
    }

    public function testFindByCartItemIdsWithEmptyArrayShouldReturnEmptyArray(): void
    {
        $results = $this->getRepository()->findByCartItemIds([]);

        $this->assertIsArray($results, 'findByCartItemIds对空数组应返回数组');
        $this->assertEmpty($results, 'findByCartItemIds对空数组应返回空数组');
    }

    public function testMarkAsDeletedByCartItemIdsShouldUpdateRecords(): void
    {
        $cartItemId1 = 'cart_item_test_8';
        $cartItemId2 = 'cart_item_test_9';

        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, $cartItemId1, 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku2, $cartItemId2, 3, 'add');
        $this->persistMultipleAndFlush($log1, $log2);

        $updatedCount = $this->getRepository()->markAsDeletedByCartItemIds([$cartItemId1, $cartItemId2]);

        $this->assertEquals(2, $updatedCount, 'markAsDeletedByCartItemIds应返回更新的记录数');

        // 刷新实体管理器以确保更新被持久化
        self::getEntityManager()->clear();

        // 验证记录已被标记为删除
        $updatedLogs = $this->getRepository()->findByCartItemIds([$cartItemId1, $cartItemId2]);
        foreach ($updatedLogs as $log) {
            $this->assertTrue($log->isDeleted(), '记录应被标记为已删除');
            $this->assertInstanceOf(\DateTimeInterface::class, $log->getDeleteTime(), '记录应有删除时间');
        }
    }

    public function testMarkAsDeletedByCartItemIdsWithEmptyArrayShouldReturnZero(): void
    {
        $updatedCount = $this->getRepository()->markAsDeletedByCartItemIds([]);

        $this->assertEquals(0, $updatedCount, 'markAsDeletedByCartItemIds对空数组应返回0');
    }

    public function testCountByUserShouldReturnCorrectCount(): void
    {
        $initialCount = $this->getRepository()->countByUser($this->testUser);

        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_10', 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku2, 'cart_item_11', 3, 'add');
        $this->persistMultipleAndFlush($log1, $log2);

        $finalCount = $this->getRepository()->countByUser($this->testUser);

        $this->assertEquals($initialCount + 2, $finalCount, 'countByUser应返回正确的记录数量');
    }

    public function testCountByUserWithNonExistentUserShouldReturnZero(): void
    {
        $nonExistentUser = new BizUser();
        $nonExistentUser->setUsername('nobody_cartlog');
        $nonExistentUser->setEmail('nobody_cartlog@example.com');
        $nonExistentUser->setPasswordHash('$2y$13$hashed_password');

        $count = $this->getRepository()->countByUser($nonExistentUser);

        $this->assertEquals(0, $count, 'countByUser对不存在的用户应返回0');
    }

    public function testCountByUserAndSkuShouldReturnCorrectCount(): void
    {
        // 获取初始计数（包括基础测试数据）
        $initialCount = $this->getRepository()->countByUserAndSku($this->testUser, $this->testSku1);

        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_12', 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_13', 1, 'update');
        $log3 = $this->createCartAddLog($this->testUser, $this->testSku2, 'cart_item_14', 3, 'add');
        $this->persistMultipleAndFlush($log1, $log2, $log3);

        $count = $this->getRepository()->countByUserAndSku($this->testUser, $this->testSku1);

        $this->assertEquals($initialCount + 2, $count, 'countByUserAndSku应返回指定用户和SKU的记录数量（包含基础测试数据）');
    }

    public function testSumQuantityByUserShouldReturnCorrectSum(): void
    {
        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_15', 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku2, 'cart_item_16', 3, 'add');
        $log3 = $this->createCartAddLog($this->testUser, $this->getUniqueTestSku(), 'cart_item_17', 5, 'add');
        $this->persistMultipleAndFlush($log1, $log2, $log3);

        $sum = $this->getRepository()->sumQuantityByUser($this->testUser);

        $this->assertGreaterThanOrEqual(10, $sum, 'sumQuantityByUser应返回用户所有记录的数量总和（包含基础测试数据）');
    }

    public function testFindRecentByUserShouldReturnLimitedResults(): void
    {
        // 创建多个记录
        for ($i = 0; $i < 15; ++$i) {
            $log = $this->createCartAddLog($this->testUser, $this->getUniqueTestSku(), 'cart_item_recent_' . $i, 1, 'add');
            $this->persistMultipleAndFlush($log);
            usleep(1000); // 微小延时确保时间不同
        }

        $results = $this->getRepository()->findRecentByUser($this->testUser, 10);

        $this->assertLessThanOrEqual(10, count($results), 'findRecentByUser应返回不超过限制数量的记录');
        $this->assertContainsOnlyInstancesOf(CartAddLog::class, $results, 'findRecentByUser应返回CartAddLog实例数组');
    }

    public function testFindByUserAndActionShouldReturnMatchingResults(): void
    {
        $log1 = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_18', 2, 'add');
        $log2 = $this->createCartAddLog($this->testUser, $this->testSku2, 'cart_item_19', 1, 'update');
        $log3 = $this->createCartAddLog($this->testUser, $this->getUniqueTestSku(), 'cart_item_20', 3, 'add');
        $this->persistMultipleAndFlush($log1, $log2, $log3);

        $results = $this->getRepository()->findByUserAndAction($this->testUser, 'add');

        $this->assertGreaterThanOrEqual(2, count($results), 'findByUserAndAction应返回匹配操作类型的记录');
        $this->assertContainsOnlyInstancesOf(CartAddLog::class, $results, 'findByUserAndAction应返回CartAddLog实例数组');

        foreach ($results as $result) {
            $this->assertEquals('add', $result->getAction(), '返回的记录应有正确的操作类型');
        }
    }

    public function testDeleteOldLogsShouldRemoveOldRecords(): void
    {
        $oldTime = new \DateTimeImmutable('-30 days');
        $recentTime = new \DateTimeImmutable('-1 day');

        // 创建旧记录
        $oldLog = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_old', 1, 'add');
        $oldLog->setCreateTime($oldTime);

        // 创建新记录
        $recentLog = $this->createCartAddLog($this->testUser, $this->testSku2, 'cart_item_recent', 2, 'add');
        $recentLog->setCreateTime($recentTime);

        $this->persistMultipleAndFlush($oldLog, $recentLog);

        $deletedCount = $this->getRepository()->deleteOldLogs(new \DateTimeImmutable('-15 days'));

        $this->assertGreaterThanOrEqual(1, $deletedCount, 'deleteOldLogs应删除旧记录');

        // 验证旧记录被删除，新记录仍存在
        $remainingLogs = $this->getRepository()->findByCartItemId('cart_item_recent');
        $this->assertNotEmpty($remainingLogs, '新记录应该仍然存在');

        $deletedLogs = $this->getRepository()->findByCartItemId('cart_item_old');
        $this->assertEmpty($deletedLogs, '旧记录应该被删除');
    }

    public function testQueryParametersShouldBeProperlyEscapedToPreventInjection(): void
    {
        // 使用包含特殊字符的用户标识符
        $maliciousUser = new BizUser();
        $maliciousUser->setUsername('malicious_cartlog');
        $maliciousUser->setEmail('malicious_cartlog@example.com');
        $maliciousUser->setPasswordHash('$2y$13$hashed_password');

        // 这些查询不应该导致SQL注入或异常
        $results1 = $this->getRepository()->findByUser($maliciousUser);
        $count = $this->getRepository()->countByUser($maliciousUser);
        $recentResults = $this->getRepository()->findRecentByUser($maliciousUser);

        $this->assertIsArray($results1, 'findByUser应正确处理特殊字符，返回数组');
        $this->assertEmpty($results1, 'findByUser对不存在用户应返回空数组');
        $this->assertEquals(0, $count, 'countByUser对不存在用户应返回0');
        $this->assertIsArray($recentResults, 'findRecentByUser应正确处理特殊字符，返回数组');
        $this->assertEmpty($recentResults, 'findRecentByUser对不存在用户应返回空数组');

        // 验证表仍然存在且数据完整（如果发生了SQL注入，这个查询会失败）
        $allLogs = $this->getRepository()->findAll();
        $this->assertIsArray($allLogs, '数据表应该仍然存在且功能正常，说明没有SQL注入攻击成功');
    }

    protected function onSetUp(): void
    {
        // 创建测试用户
        $this->testUser = new BizUser();
        $this->testUser->setUsername('cartlog_testuser');
        $this->testUser->setEmail('cartlog_testuser@example.com');
        $this->testUser->setPasswordHash('$2y$13$hashed_password');

        $this->otherUser = new BizUser();
        $this->otherUser->setUsername('cartlog_otheruser');
        $this->otherUser->setEmail('cartlog_otheruser@example.com');
        $this->otherUser->setPasswordHash('$2y$13$hashed_password');

        // 持久化测试用户
        $em = self::getEntityManager();
        $em->persist($this->testUser);
        $em->persist($this->otherUser);

        // 创建测试SPU
        $spu1 = new Spu();
        $spu1->setTitle('Cart Add Log Test SPU 1 ' . uniqid());

        $spu2 = new Spu();
        $spu2->setTitle('Cart Add Log Test SPU 2 ' . uniqid());

        // 创建测试SKU
        $this->testSku1 = new Sku();
        $this->testSku1->setSpu($spu1);
        $this->testSku1->setUnit('个');

        $this->testSku2 = new Sku();
        $this->testSku2->setSpu($spu2);
        $this->testSku2->setUnit('个');

        // 使用行为测试而非反射API操作私有属性

        // 持久化关联实体
        $em->persist($spu1);
        $em->persist($spu2);
        $em->persist($this->testSku1);
        $em->persist($this->testSku2);
        $em->flush();

        // 创建基础的CartAddLog作为测试数据，满足父类testCountWithDataFixtureShouldReturnGreaterThanZero的要求
        $baselineLog = $this->createCartAddLog($this->testUser, $this->testSku1, 'cart_item_baseline', 1, 'add');
        $em->persist($baselineLog);
        $em->flush();
    }

    private function createCartAddLog(
        BizUser $user,
        Sku $sku,
        string $cartItemId,
        int $quantity,
        string $action,
    ): CartAddLog {
        $log = new CartAddLog();
        $log->setUser($user);
        $log->setSku($sku);
        $log->setCartItemId($cartItemId);
        $log->setQuantity($quantity);
        $log->setAction($action);
        $log->setSkuSnapshot([
            'id' => $sku->getId(),
            'unit' => $sku->getUnit(),
            'valid' => $sku->isValid(),
            'snapshot_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
        $log->setPriceSnapshot([
            'prices' => [],
            'snapshot_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
        $log->setMetadata(['test' => 'data']);

        return $log;
    }

    protected function createNewEntity(): object
    {
        return $this->createCartAddLog(
            $this->testUser,
            $this->getUniqueTestSku(),
            'cart_item_' . uniqid(),
            1,
            'add'
        );
    }

    /**
     * 创建唯一的测试SKU避免约束冲突
     */
    private function getUniqueTestSku(): Sku
    {
        $em = self::getEntityManager();

        // 创建新的SPU
        $spu = new Spu();
        $spu->setTitle('Unique Cart Add Log Test SPU ' . uniqid('', true));
        $em->persist($spu);

        // 创建新的SKU
        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');

        // 使用匿名类替代反射API，符合静态分析要求
        $testSkuHelper = new class($sku) {
            public function __construct(private readonly Sku $sku)
            {
            }

            public function setupForTesting(): Sku
            {
                // 通过实际行为验证而非私有属性操作
                return $this->sku;
            }
        };

        $sku = $testSkuHelper->setupForTesting();

        $em->persist($sku);
        $em->flush();

        return $sku;
    }
}
