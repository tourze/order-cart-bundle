<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\CartItemNotFoundException;
use Tourze\OrderCartBundle\Exception\CartLimitExceededException;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\CartManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * CartManager 集成测试
 *
 * @internal
 */
#[CoversClass(CartManager::class)]
#[RunTestsInSeparateProcesses]
final class CartManagerTest extends AbstractIntegrationTestCase
{
    private CartManager $cartManager;
    private CartItemRepository $repository;
    private UserInterface $testUser;
    private Sku $testSku;

    protected function onSetUp(): void
    {
        $this->cartManager = self::getService(CartManager::class);
        $this->repository = self::getService(CartItemRepository::class);
        $this->testUser = $this->createUser('cartmanager_test_user', 'test_password', ['ROLE_USER']);
        $this->testSku = $this->createTestSkuWithStock();
    }

    private function createTestSkuWithStock(): Sku
    {
        $em = self::getEntityManager();

        // 创建 SPU
        $spu = new Spu();
        $spu->setTitle('Test Product for CartManager ' . uniqid());
        $em->persist($spu);

        // 创建 SKU
        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        $sku->setMarketPrice('99.99');
        $em->persist($sku);

        $em->flush();

        // 创建库存批次记录
        $stockBatch = new StockBatch();
        $stockBatch->setSku($sku);
        $stockBatch->setBatchNo('BATCH-' . uniqid());
        $stockBatch->setQuantity(1000);
        $stockBatch->setAvailableQuantity(1000);
        $stockBatch->setStatus('available');
        $em->persist($stockBatch);
        $em->flush();

        return $sku;
    }

    private function createAnotherTestSkuWithStock(): Sku
    {
        $em = self::getEntityManager();

        $spu = new Spu();
        $spu->setTitle('Another Test Product ' . uniqid());
        $em->persist($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        $sku->setMarketPrice('199.99');
        $em->persist($sku);

        $em->flush();

        $stockBatch = new StockBatch();
        $stockBatch->setSku($sku);
        $stockBatch->setBatchNo('BATCH-' . uniqid());
        $stockBatch->setQuantity(500);
        $stockBatch->setAvailableQuantity(500);
        $stockBatch->setStatus('available');
        $em->persist($stockBatch);
        $em->flush();

        return $sku;
    }

    public function testAddItemSuccess(): void
    {
        $result = $this->cartManager->addItem($this->testUser, $this->testSku, 2, ['note' => 'test']);

        $this->assertInstanceOf(CartItem::class, $result);
        $this->assertEquals(2, $result->getQuantity());
        $this->assertEquals($this->testSku->getId(), $result->getSku()->getId());
        $this->assertTrue($result->isSelected());
        $this->assertArrayHasKey('note', $result->getMetadata());

        // 验证数据已持久化
        $found = $this->repository->findByUserAndSku($this->testUser, $this->testSku);
        $this->assertNotNull($found);
        $this->assertEquals(2, $found->getQuantity());
    }

    public function testAddItemToExistingCartItemIncreasesQuantity(): void
    {
        // 第一次添加
        $this->cartManager->addItem($this->testUser, $this->testSku, 2);

        // 第二次添加同一SKU
        $result = $this->cartManager->addItem($this->testUser, $this->testSku, 3);

        $this->assertEquals(5, $result->getQuantity());

        // 确认购物车中只有一个条目
        $count = $this->repository->countByUser($this->testUser);
        $this->assertEquals(1, $count);
    }

    public function testAddItemInvalidQuantityZero(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('新增数量必须大于0');

        $this->cartManager->addItem($this->testUser, $this->testSku, 0);
    }

    public function testAddItemInvalidQuantityNegative(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('新增数量必须大于0');

        $this->cartManager->addItem($this->testUser, $this->testSku, -1);
    }

    public function testAddItemExceedMaxQuantityPerItem(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('超出最大购买数量');

        $this->cartManager->addItem($this->testUser, $this->testSku, 1000);
    }

    public function testAddItemInvalidSku(): void
    {
        // 创建一个未持久化的SKU（模拟无效SKU）
        $invalidSpu = new Spu();
        $invalidSpu->setTitle('Invalid Product');

        $invalidSku = new Sku();
        $invalidSku->setSpu($invalidSpu);
        $invalidSku->setUnit('个');

        $this->expectException(InvalidSkuException::class);

        $this->cartManager->addItem($this->testUser, $invalidSku, 1);
    }

    public function testUpdateQuantitySuccess(): void
    {
        // 先添加购物车项
        $cartItem = $this->cartManager->addItem($this->testUser, $this->testSku, 2);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        // 更新数量
        $result = $this->cartManager->updateQuantity($this->testUser, $cartItemId, 5);

        $this->assertEquals(5, $result->getQuantity());

        // 验证持久化
        $found = $this->repository->findByUserAndId($this->testUser, $cartItemId);
        $this->assertNotNull($found);
        $this->assertEquals(5, $found->getQuantity());
    }

    public function testUpdateQuantityInvalidQuantityZero(): void
    {
        $cartItem = $this->cartManager->addItem($this->testUser, $this->testSku, 2);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        $this->expectException(InvalidQuantityException::class);

        $this->cartManager->updateQuantity($this->testUser, $cartItemId, 0);
    }

    public function testUpdateQuantityCartItemNotFound(): void
    {
        $this->expectException(CartItemNotFoundException::class);

        $this->cartManager->updateQuantity($this->testUser, 'nonexistent-id', 5);
    }

    public function testRemoveItemSuccess(): void
    {
        $cartItem = $this->cartManager->addItem($this->testUser, $this->testSku, 2);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        $this->cartManager->removeItem($this->testUser, $cartItemId);

        // 验证已删除
        $found = $this->repository->findByUserAndId($this->testUser, $cartItemId);
        $this->assertNull($found);
    }

    public function testRemoveItemNotFound(): void
    {
        $this->expectException(CartItemNotFoundException::class);

        $this->cartManager->removeItem($this->testUser, 'nonexistent-id');
    }

    public function testClearCartSuccess(): void
    {
        // 添加多个购物车项
        $this->cartManager->addItem($this->testUser, $this->testSku, 2);
        $anotherSku = $this->createAnotherTestSkuWithStock();
        $this->cartManager->addItem($this->testUser, $anotherSku, 3);

        // 验证有2个项目
        $this->assertEquals(2, $this->repository->countByUser($this->testUser));

        // 清空购物车
        $count = $this->cartManager->clearCart($this->testUser);

        $this->assertEquals(2, $count);
        $this->assertEquals(0, $this->repository->countByUser($this->testUser));
    }

    public function testClearEmptyCart(): void
    {
        $count = $this->cartManager->clearCart($this->testUser);

        $this->assertEquals(0, $count);
    }

    public function testUpdateSelectionSuccess(): void
    {
        $cartItem = $this->cartManager->addItem($this->testUser, $this->testSku, 2);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        // 默认是选中的，取消选中
        $result = $this->cartManager->updateSelection($this->testUser, $cartItemId, false);

        $this->assertFalse($result->isSelected());

        // 验证持久化
        $found = $this->repository->findByUserAndId($this->testUser, $cartItemId);
        $this->assertNotNull($found);
        $this->assertFalse($found->isSelected());
    }

    public function testUpdateSelectionNotFound(): void
    {
        $this->expectException(CartItemNotFoundException::class);

        $this->cartManager->updateSelection($this->testUser, 'nonexistent-id', true);
    }

    public function testBatchUpdateSelectionSuccess(): void
    {
        $cartItem1 = $this->cartManager->addItem($this->testUser, $this->testSku, 2);
        $anotherSku = $this->createAnotherTestSkuWithStock();
        $cartItem2 = $this->cartManager->addItem($this->testUser, $anotherSku, 3);

        $cartItemId1 = $cartItem1->getId();
        $cartItemId2 = $cartItem2->getId();
        $this->assertNotNull($cartItemId1);
        $this->assertNotNull($cartItemId2);

        // 批量取消选中
        $results = $this->cartManager->batchUpdateSelection($this->testUser, [$cartItemId1, $cartItemId2], false);

        $this->assertCount(2, $results);

        foreach ($results as $item) {
            $this->assertFalse($item->isSelected());
        }

        // 验证持久化
        $found1 = $this->repository->findByUserAndId($this->testUser, $cartItemId1);
        $found2 = $this->repository->findByUserAndId($this->testUser, $cartItemId2);
        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertFalse($found1->isSelected());
        $this->assertFalse($found2->isSelected());
    }

    public function testBatchUpdateSelectionEmptyArray(): void
    {
        $results = $this->cartManager->batchUpdateSelection($this->testUser, [], true);

        $this->assertEmpty($results);
    }

    public function testGetCartItemCount(): void
    {
        $this->assertEquals(0, $this->cartManager->getCartItemCount($this->testUser));

        $this->cartManager->addItem($this->testUser, $this->testSku, 2);

        $this->assertEquals(1, $this->cartManager->getCartItemCount($this->testUser));

        $anotherSku = $this->createAnotherTestSkuWithStock();
        $this->cartManager->addItem($this->testUser, $anotherSku, 3);

        $this->assertEquals(2, $this->cartManager->getCartItemCount($this->testUser));
    }

    public function testGetCartTotalQuantity(): void
    {
        $this->assertEquals(0, $this->cartManager->getCartTotalQuantity($this->testUser));

        $this->cartManager->addItem($this->testUser, $this->testSku, 2);

        $this->assertEquals(2, $this->cartManager->getCartTotalQuantity($this->testUser));

        $anotherSku = $this->createAnotherTestSkuWithStock();
        $this->cartManager->addItem($this->testUser, $anotherSku, 3);

        $this->assertEquals(5, $this->cartManager->getCartTotalQuantity($this->testUser));
    }

    public function testUserIsolation(): void
    {
        // 用户1添加购物车
        $this->cartManager->addItem($this->testUser, $this->testSku, 2);

        // 创建另一个用户
        $otherUser = $this->createUser('other_cartmanager_user', 'password', ['ROLE_USER']);

        // 用户2的购物车应该为空
        $this->assertEquals(0, $this->cartManager->getCartItemCount($otherUser));
        $this->assertEquals(0, $this->cartManager->getCartTotalQuantity($otherUser));

        // 用户2添加购物车
        $anotherSku = $this->createAnotherTestSkuWithStock();
        $this->cartManager->addItem($otherUser, $anotherSku, 5);

        // 验证两个用户的购物车互不影响
        $this->assertEquals(1, $this->cartManager->getCartItemCount($this->testUser));
        $this->assertEquals(2, $this->cartManager->getCartTotalQuantity($this->testUser));
        $this->assertEquals(1, $this->cartManager->getCartItemCount($otherUser));
        $this->assertEquals(5, $this->cartManager->getCartTotalQuantity($otherUser));
    }

    public function testCartLimitExceeded(): void
    {
        // 先填满购物车到限制数量（100个）
        for ($i = 0; $i < 100; ++$i) {
            $sku = $this->createAnotherTestSkuWithStock();
            $this->cartManager->addItem($this->testUser, $sku, 1);
        }

        $this->assertEquals(100, $this->cartManager->getCartItemCount($this->testUser));

        // 尝试添加第101个应该失败
        $this->expectException(CartLimitExceededException::class);

        $extraSku = $this->createAnotherTestSkuWithStock();
        $this->cartManager->addItem($this->testUser, $extraSku, 1);
    }
}
