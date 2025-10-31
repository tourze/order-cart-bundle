<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\PriceCalculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\OrderCartBundle\Exception\InvalidArgumentException;
use Tourze\OrderCartBundle\PriceCalculator\DefaultPriceCalculator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DefaultPriceCalculator::class)]
#[RunTestsInSeparateProcesses]
final class DefaultPriceCalculatorTest extends AbstractIntegrationTestCase
{
    private DefaultPriceCalculator $calculator;

    protected function onSetUp(): void
    {
        $this->calculator = self::getService(DefaultPriceCalculator::class);
    }

    public function testCalculateItemPrice(): void
    {
        $product = new ProductDTO(
            skuId: 'SKU001',
            name: 'Test Product',
            price: '10.99',
            stock: 100,
            isActive: true
        );

        $cartItem = new CartItemDTO(
            id: '1',
            product: $product,
            quantity: 3,
            selected: true,
            metadata: [],
            createTime: new \DateTimeImmutable(),
            updateTime: new \DateTimeImmutable()
        );

        $price = $this->calculator->calculateItemPrice($cartItem);

        $this->assertSame('32.97', $price);
    }

    public function testCalculateItemPriceWithZeroQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');

        $product = new ProductDTO(
            skuId: 'SKU001',
            name: 'Test Product',
            price: '10.99',
            stock: 100,
            isActive: true
        );

        new CartItemDTO(
            id: '1',
            product: $product,
            quantity: 0,
            selected: true,
            metadata: [],
            createTime: new \DateTimeImmutable(),
            updateTime: new \DateTimeImmutable()
        );
    }

    public function testCalculateTotalPrice(): void
    {
        $product1 = new ProductDTO(
            skuId: 'SKU001',
            name: 'Product 1',
            price: '10.00',
            stock: 100,
            isActive: true
        );

        $product2 = new ProductDTO(
            skuId: 'SKU002',
            name: 'Product 2',
            price: '15.00',
            stock: 50,
            isActive: true
        );

        $cartItem1 = new CartItemDTO(
            id: '1',
            product: $product1,
            quantity: 2,
            selected: true,
            metadata: [],
            createTime: new \DateTimeImmutable(),
            updateTime: new \DateTimeImmutable()
        );

        $cartItem2 = new CartItemDTO(
            id: '2',
            product: $product2,
            quantity: 3,
            selected: true,
            metadata: [],
            createTime: new \DateTimeImmutable(),
            updateTime: new \DateTimeImmutable()
        );

        $items = [$cartItem1, $cartItem2];
        $total = $this->calculator->calculateTotalPrice($items);

        $this->assertSame('65.00', $total);
    }

    public function testCalculateSelectedPrice(): void
    {
        $product1 = new ProductDTO(
            skuId: 'SKU001',
            name: 'Product 1',
            price: '10.00',
            stock: 100,
            isActive: true
        );

        $product2 = new ProductDTO(
            skuId: 'SKU002',
            name: 'Product 2',
            price: '15.00',
            stock: 50,
            isActive: true
        );

        $cartItem1 = new CartItemDTO(
            id: '1',
            product: $product1,
            quantity: 2,
            selected: true,
            metadata: [],
            createTime: new \DateTimeImmutable(),
            updateTime: new \DateTimeImmutable()
        );

        $cartItem2 = new CartItemDTO(
            id: '2',
            product: $product2,
            quantity: 3,
            selected: false,
            metadata: [],
            createTime: new \DateTimeImmutable(),
            updateTime: new \DateTimeImmutable()
        );

        $items = [$cartItem1, $cartItem2];
        $selectedTotal = $this->calculator->calculateSelectedPrice($items);

        $this->assertSame('20.00', $selectedTotal);
    }

    public function testApplyDiscount(): void
    {
        // Test percentage discount
        $discountedPrice = $this->calculator->applyDiscount('100.00', '10.0', 'percentage');
        $this->assertSame('90.00', $discountedPrice);

        // Test fixed discount
        $discountedPrice = $this->calculator->applyDiscount('100.00', '10.00', 'fixed');
        $this->assertSame('90.00', $discountedPrice);

        // Test no discount
        $discountedPrice = $this->calculator->applyDiscount('100.00', '0', 'percentage');
        $this->assertSame('100.00', $discountedPrice);
    }

    public function testDiscountDoesNotMakePriceNegative(): void
    {
        // Test large percentage discount
        $discountedPrice = $this->calculator->applyDiscount('10.00', '150.0', 'percentage');
        $this->assertSame('0.00', $discountedPrice);

        // Test large fixed discount
        $discountedPrice = $this->calculator->applyDiscount('10.00', '20.00', 'fixed');
        $this->assertSame('0.00', $discountedPrice);
    }
}
