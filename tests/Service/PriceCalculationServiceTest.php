<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartTotalResponse;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Service\PriceCalculationService;
use Tourze\OrderCartBundle\Service\PriceCalculationServiceInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * PriceCalculationService 集成测试
 *
 * @internal
 */
#[CoversClass(PriceCalculationService::class)]
#[RunTestsInSeparateProcesses]
final class PriceCalculationServiceTest extends AbstractIntegrationTestCase
{
    private PriceCalculationService $service;
    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->service = self::getService(PriceCalculationService::class);
        $this->testUser = $this->createUser('price_calc_test_user', 'test_password', ['ROLE_USER']);
    }

    private function createTestSkuWithStock(string $marketPrice = '99.99'): Sku
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

    public function testServiceImplementsRequiredInterface(): void
    {
        $this->assertInstanceOf(PriceCalculationServiceInterface::class, $this->service);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(PriceCalculationService::class, $this->service);
        $this->assertInstanceOf(PriceCalculationServiceInterface::class, $this->service);
    }

    public function testCalculateCartTotalWithEmptyCart(): void
    {
        $result = $this->service->calculateCartTotal($this->testUser, []);

        $this->assertInstanceOf(CartTotalResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('0.00', $result->originalAmount);
        $this->assertEquals('0.00', $result->productAmount);
        $this->assertEquals('0.00', $result->discountAmount);
        $this->assertEquals('0.00', $result->shippingFee);
        $this->assertEquals('0.00', $result->totalAmount);
    }

    public function testCalculateCartTotalWithSingleItem(): void
    {
        $sku = $this->createTestSkuWithStock('100.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 2, true);

        $result = $this->service->calculateCartTotal($this->testUser, [$cartItem]);

        $this->assertInstanceOf(CartTotalResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('200.00', $result->originalAmount);
    }

    public function testCalculateCartTotalWithMultipleItems(): void
    {
        $sku1 = $this->createTestSkuWithStock('100.00');
        $sku2 = $this->createTestSkuWithStock('50.00');

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 2, true); // 200.00
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 3, true); // 150.00

        $result = $this->service->calculateCartTotal($this->testUser, [$cartItem1, $cartItem2]);

        $this->assertInstanceOf(CartTotalResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('350.00', $result->originalAmount);
    }

    public function testCalculateCartTotalOnlyCountsSelectedItems(): void
    {
        $sku1 = $this->createTestSkuWithStock('100.00');
        $sku2 = $this->createTestSkuWithStock('50.00');

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 2, true);  // 200.00 (选中)
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 3, false); // 150.00 (未选中)

        $result = $this->service->calculateCartTotal($this->testUser, [$cartItem1, $cartItem2]);

        $this->assertInstanceOf(CartTotalResponse::class, $result);
        $this->assertTrue($result->success);
        // 只有选中的项目被计入
        $this->assertEquals('200.00', $result->originalAmount);
    }

    public function testCalculateProductTotal(): void
    {
        $sku1 = $this->createTestSkuWithStock('100.00');
        $sku2 = $this->createTestSkuWithStock('50.00');

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 2, true);  // 200.00
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 3, true);  // 150.00

        $total = $this->service->calculateProductTotal([$cartItem1, $cartItem2]);

        $this->assertEquals('350.00', $total);
    }

    public function testCalculateProductTotalOnlyCountsSelectedItems(): void
    {
        $sku1 = $this->createTestSkuWithStock('100.00');
        $sku2 = $this->createTestSkuWithStock('50.00');

        $cartItem1 = $this->createCartItem($this->testUser, $sku1, 2, true);  // 200.00 (选中)
        $cartItem2 = $this->createCartItem($this->testUser, $sku2, 3, false); // 150.00 (未选中)

        $total = $this->service->calculateProductTotal([$cartItem1, $cartItem2]);

        $this->assertEquals('200.00', $total);
    }

    public function testCalculateProductTotalSkipsItemsWithoutPrice(): void
    {
        $skuWithPrice = $this->createTestSkuWithStock('100.00');
        $skuNoPrice = $this->createTestSkuWithoutPrice();

        $cartItem1 = $this->createCartItem($this->testUser, $skuWithPrice, 2, true);
        $cartItem2 = $this->createCartItem($this->testUser, $skuNoPrice, 3, true);

        $total = $this->service->calculateProductTotal([$cartItem1, $cartItem2]);

        // 只有有价格的商品被计入
        $this->assertEquals('200.00', $total);
    }

    public function testCalculatePromotionDiscount(): void
    {
        // 测试满500减50
        $sku = $this->createTestSkuWithStock('250.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 3, true); // 750.00

        $result = $this->service->calculatePromotionDiscount($this->testUser, [$cartItem]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('discountAmount', $result);
        $this->assertArrayHasKey('discountDetails', $result);

        // 750 >= 500，应该有满500减50
        $this->assertEquals('50.00', $result['discountAmount']);
        $this->assertNotEmpty($result['discountDetails']);
    }

    public function testCalculatePromotionDiscountFor200Threshold(): void
    {
        // 测试满200减20
        $sku = $this->createTestSkuWithStock('100.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 3, true); // 300.00

        $result = $this->service->calculatePromotionDiscount($this->testUser, [$cartItem]);

        $this->assertIsArray($result);
        // 300 >= 200 但 < 500，应该有满200减20
        $this->assertEquals('20.00', $result['discountAmount']);
    }

    public function testCalculatePromotionDiscountNoDiscount(): void
    {
        // 测试不满足任何阈值
        $sku = $this->createTestSkuWithStock('50.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 2, true); // 100.00

        $result = $this->service->calculatePromotionDiscount($this->testUser, [$cartItem]);

        $this->assertIsArray($result);
        // 100 < 200，没有满减优惠
        $this->assertEquals('0.00', $result['discountAmount']);
    }

    public function testCalculatePromotionDiscountFreeShipping(): void
    {
        // 测试满99免邮
        $sku = $this->createTestSkuWithStock('50.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 2, true); // 100.00

        $result = $this->service->calculatePromotionDiscount($this->testUser, [$cartItem]);

        $this->assertIsArray($result);
        // 100 >= 99，应该有免邮优惠
        $discountDetails = $result['discountDetails'];
        $hasFreeShipping = false;
        foreach ($discountDetails as $detail) {
            if ($detail->type === 'free-freight') {
                $hasFreeShipping = true;
                break;
            }
        }
        $this->assertTrue($hasFreeShipping);
    }

    public function testCalculateShippingFee(): void
    {
        $sku = $this->createTestSkuWithStock('100.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 1, true);

        $fee = $this->service->calculateShippingFee($this->testUser, [$cartItem]);

        // 默认运费为 10.00
        $this->assertEquals('10.00', $fee);
    }

    public function testCalculateShippingFeeWithEmptyCart(): void
    {
        $fee = $this->service->calculateShippingFee($this->testUser, []);

        $this->assertEquals('0.00', $fee);
    }

    public function testCheckPriceChanges(): void
    {
        $sku = $this->createTestSkuWithStock('100.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 1, true);

        $changes = $this->service->checkPriceChanges([$cartItem]);

        // 由于实现中 oldPrice 和 marketPrice 相同，应该没有变化
        $this->assertIsArray($changes);
        $this->assertEmpty($changes);
    }

    public function testCalculateCartTotalWithFreeShipping(): void
    {
        // 测试当满足免邮条件时，运费应该为0
        $sku = $this->createTestSkuWithStock('50.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 2, true); // 100.00，满足99免邮

        $result = $this->service->calculateCartTotal($this->testUser, [$cartItem]);

        $this->assertInstanceOf(CartTotalResponse::class, $result);
        $this->assertTrue($result->success);
        // 满足免邮条件，运费应该为0
        $this->assertEquals('0.00', $result->shippingFee);
    }

    public function testCalculateCartTotalReturnsDiscountDetails(): void
    {
        $sku = $this->createTestSkuWithStock('250.00');
        $cartItem = $this->createCartItem($this->testUser, $sku, 3, true); // 750.00

        $result = $this->service->calculateCartTotal($this->testUser, [$cartItem]);

        $this->assertInstanceOf(CartTotalResponse::class, $result);
        $this->assertTrue($result->success);

        $discountDetails = $result->discountDetails;
        $this->assertIsArray($discountDetails);
        $this->assertNotEmpty($discountDetails);
    }
}
