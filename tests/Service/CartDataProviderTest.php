<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\CartException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\CartDataProvider;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * CartDataProvider 集成测试
 *
 * @internal
 */
#[CoversClass(CartDataProvider::class)]
#[RunTestsInSeparateProcesses]
final class CartDataProviderTest extends AbstractIntegrationTestCase
{
    private CartDataProvider $dataProvider;
    private CartItemRepository $repository;
    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->dataProvider = self::getService(CartDataProvider::class);
        $this->repository = self::getService(CartItemRepository::class);
        $this->testUser = $this->createUser('cart_data_provider_test_user', 'test_password', ['ROLE_USER']);
    }

    private function createTestSkuWithStock(string $marketPrice = '99.99', int $stockQuantity = 1000): Sku
    {
        $em = self::getEntityManager();

        $spu = new Spu();
        $spu->setTitle('Test Product ' . uniqid());
        $em->persist($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        $sku->setMarketPrice($marketPrice);
        $em->persist($sku);

        $em->flush();

        // 创建库存批次记录
        $stockBatch = new StockBatch();
        $stockBatch->setSku($sku);
        $stockBatch->setBatchNo('BATCH-' . uniqid());
        $stockBatch->setQuantity($stockQuantity);
        $stockBatch->setAvailableQuantity($stockQuantity);
        $stockBatch->setStatus('available');
        $em->persist($stockBatch);
        $em->flush();

        return $sku;
    }

    private function createTestSkuWithoutPrice(): Sku
    {
        $em = self::getEntityManager();

        $spu = new Spu();
        $spu->setTitle('Test Product Without Price ' . uniqid());
        $em->persist($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        // 不设置价格
        $em->persist($sku);

        $em->flush();

        return $sku;
    }

    private function createCartItem(UserInterface $user, Sku $sku, int $quantity = 1, bool $selected = true): CartItem
    {
        $em = self::getEntityManager();

        $cartItem = new CartItem();
        $cartItem->setUser($user);
        $cartItem->setSku($sku);
        $cartItem->setQuantity($quantity);
        $cartItem->setSelected($selected);
        $em->persist($cartItem);
        $em->flush();

        return $cartItem;
    }

    public function testGetCartSummaryWithItems(): void
    {
        // 创建带价格的SKU
        $sku1 = $this->createTestSkuWithStock('100.00');
        $sku2 = $this->createTestSkuWithStock('50.00');

        // 创建购物车项
        $this->createCartItem($this->testUser, $sku1, 2, true);  // 200.00
        $this->createCartItem($this->testUser, $sku2, 1, false); // 50.00 (未选中)

        $summary = $this->dataProvider->getCartSummary($this->testUser);

        $this->assertInstanceOf(CartSummaryDTO::class, $summary);
        $this->assertEquals(2, $summary->getTotalItems());
        $this->assertEquals(1, $summary->getSelectedItems());
        $this->assertEquals('200.00', $summary->getSelectedAmount());
        $this->assertEquals('250.00', $summary->getTotalAmount());
    }

    public function testGetCartSummaryWithItemsWithoutPrice(): void
    {
        // 创建无价格的SKU
        $skuNoPrice = $this->createTestSkuWithoutPrice();
        $skuWithPrice = $this->createTestSkuWithStock('100.00');

        // 创建购物车项
        $this->createCartItem($this->testUser, $skuNoPrice, 2, true);
        $this->createCartItem($this->testUser, $skuWithPrice, 1, true);

        $summary = $this->dataProvider->getCartSummary($this->testUser);

        $this->assertInstanceOf(CartSummaryDTO::class, $summary);
        $this->assertEquals(2, $summary->getTotalItems());
        // 只有有价格的商品被计入选中金额
        $this->assertEquals(1, $summary->getSelectedItems());
        $this->assertEquals('100.00', $summary->getSelectedAmount());
        $this->assertEquals('100.00', $summary->getTotalAmount());
    }

    public function testGetCartSummaryEmpty(): void
    {
        $summary = $this->dataProvider->getCartSummary($this->testUser);

        $this->assertInstanceOf(CartSummaryDTO::class, $summary);
        $this->assertEquals(0, $summary->getTotalItems());
        $this->assertEquals(0, $summary->getSelectedItems());
        $this->assertEquals('0.00', $summary->getSelectedAmount());
        $this->assertEquals('0.00', $summary->getTotalAmount());
    }

    public function testGetCartItemsWithItems(): void
    {
        $sku = $this->createTestSkuWithStock('99.99');
        $this->createCartItem($this->testUser, $sku, 2, true);

        $items = $this->dataProvider->getCartItems($this->testUser);

        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertInstanceOf(CartItemDTO::class, $items[0]);
        $this->assertEquals(2, $items[0]->getQuantity());
        $this->assertTrue($items[0]->isSelected());
    }

    public function testGetCartItemsEmpty(): void
    {
        $items = $this->dataProvider->getCartItems($this->testUser);

        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function testGetCartItemsSkipsItemsWithoutPrice(): void
    {
        $skuNoPrice = $this->createTestSkuWithoutPrice();
        $skuWithPrice = $this->createTestSkuWithStock('50.00');

        $this->createCartItem($this->testUser, $skuNoPrice, 1, true);
        $this->createCartItem($this->testUser, $skuWithPrice, 2, true);

        $items = $this->dataProvider->getCartItems($this->testUser);

        // 只返回有价格的商品
        $this->assertCount(1, $items);
        $this->assertEquals(2, $items[0]->getQuantity());
    }

    public function testGetSelectedItems(): void
    {
        $sku1 = $this->createTestSkuWithStock('100.00');
        $sku2 = $this->createTestSkuWithStock('50.00');

        $this->createCartItem($this->testUser, $sku1, 2, true);  // 选中
        $this->createCartItem($this->testUser, $sku2, 1, false); // 未选中

        $items = $this->dataProvider->getSelectedItems($this->testUser);

        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertTrue($items[0]->isSelected());
        $this->assertEquals(2, $items[0]->getQuantity());
    }

    public function testGetSelectedItemsEmpty(): void
    {
        $items = $this->dataProvider->getSelectedItems($this->testUser);

        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function testGetItemCount(): void
    {
        $this->assertEquals(0, $this->dataProvider->getItemCount($this->testUser));

        $sku1 = $this->createTestSkuWithStock();
        $sku2 = $this->createTestSkuWithStock();

        $this->createCartItem($this->testUser, $sku1, 1);
        $this->createCartItem($this->testUser, $sku2, 3);

        $count = $this->dataProvider->getItemCount($this->testUser);

        $this->assertEquals(2, $count);
    }

    public function testGetItemByIdFound(): void
    {
        $sku = $this->createTestSkuWithStock('88.88');
        $cartItem = $this->createCartItem($this->testUser, $sku, 3, true);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        $item = $this->dataProvider->getItemById($this->testUser, $cartItemId);

        $this->assertInstanceOf(CartItemDTO::class, $item);
        $this->assertEquals($cartItemId, $item->getId());
        $this->assertEquals(3, $item->getQuantity());
        $this->assertTrue($item->isSelected());
        $this->assertEquals('88.88', $item->getProduct()->getPrice());
    }

    public function testGetItemByIdNotFound(): void
    {
        $item = $this->dataProvider->getItemById($this->testUser, 'nonexistent-id');

        $this->assertNull($item);
    }

    public function testGetItemByIdWithoutPriceThrowsException(): void
    {
        $skuNoPrice = $this->createTestSkuWithoutPrice();
        $cartItem = $this->createCartItem($this->testUser, $skuNoPrice, 1, true);
        $cartItemId = $cartItem->getId();
        $this->assertNotNull($cartItemId);

        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Product not found for SKU');

        $this->dataProvider->getItemById($this->testUser, $cartItemId);
    }

    public function testGetSelectedCartEntities(): void
    {
        $sku1 = $this->createTestSkuWithStock();
        $sku2 = $this->createTestSkuWithStock();

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 2, true);
        $this->createCartItem($this->testUser, $sku2, 1, false); // 未选中

        $entities = $this->dataProvider->getSelectedCartEntities($this->testUser);

        $this->assertCount(1, $entities);
        $this->assertInstanceOf(CartItem::class, $entities[0]);
        $this->assertEquals($cartItem1->getId(), $entities[0]->getId());
        $this->assertTrue($entities[0]->isSelected());
    }

    public function testGetSelectedCartEntitiesEmpty(): void
    {
        $entities = $this->dataProvider->getSelectedCartEntities($this->testUser);

        $this->assertIsArray($entities);
        $this->assertEmpty($entities);
    }

    public function testUserIsolation(): void
    {
        // 用户1添加购物车
        $sku = $this->createTestSkuWithStock();
        $this->createCartItem($this->testUser, $sku, 2, true);

        // 创建另一个用户
        $otherUser = $this->createUser('other_cart_data_user', 'password', ['ROLE_USER']);

        // 用户2的购物车应该为空
        $this->assertEquals(0, $this->dataProvider->getItemCount($otherUser));

        $summary = $this->dataProvider->getCartSummary($otherUser);
        $this->assertEquals(0, $summary->getTotalItems());

        // 用户1的购物车有数据
        $this->assertEquals(1, $this->dataProvider->getItemCount($this->testUser));
    }

    public function testProductDTOContainsCorrectData(): void
    {
        $sku = $this->createTestSkuWithStock('199.99', 500);
        $this->createCartItem($this->testUser, $sku, 1, true);

        $items = $this->dataProvider->getCartItems($this->testUser);

        $this->assertCount(1, $items);
        $product = $items[0]->getProduct();

        $this->assertEquals($sku->getId(), $product->getSkuId());
        $this->assertEquals('199.99', $product->getPrice());
        // 库存数量从 StockService 获取
        $this->assertGreaterThanOrEqual(0, $product->getStock());
    }
}
