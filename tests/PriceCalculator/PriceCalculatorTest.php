<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\PriceCalculator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\OrderCartBundle\PriceCalculator\DefaultPriceCalculator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DefaultPriceCalculator::class)]
#[RunTestsInSeparateProcesses]
final class PriceCalculatorTest extends AbstractIntegrationTestCase
{
    private DefaultPriceCalculator $calculator;

    protected function onSetUp(): void
    {
        $this->calculator = self::getService(DefaultPriceCalculator::class);
    }

    public function testCalculateItemPrice(): void
    {
        $product = new ProductDTO(
            '1',
            'Product 1',
            '10.00',
            100,
            true
        );

        $cartItem = new CartItemDTO(
            '1',
            $product,
            3,
            true,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $price = $this->calculator->calculateItemPrice($cartItem);

        $this->assertEquals('30.00', $price);
    }

    public function testCalculateTotalPrice(): void
    {
        $product1 = new ProductDTO(
            '1',
            'Product 1',
            '10.00',
            100,
            true
        );

        $product2 = new ProductDTO(
            '2',
            'Product 2',
            '20.00',
            50,
            true
        );

        $cartItem1 = new CartItemDTO(
            '1',
            $product1,
            2,
            true,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $cartItem2 = new CartItemDTO(
            '2',
            $product2,
            1,
            false,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $items = [$cartItem1, $cartItem2];
        $total = $this->calculator->calculateTotalPrice($items);

        $this->assertEquals('40.00', $total);
    }

    public function testCalculateSelectedPrice(): void
    {
        $product1 = new ProductDTO(
            '1',
            'Product 1',
            '10.00',
            100,
            true
        );

        $product2 = new ProductDTO(
            '2',
            'Product 2',
            '20.00',
            50,
            true
        );

        $cartItem1 = new CartItemDTO(
            '1',
            $product1,
            2,
            true,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $cartItem2 = new CartItemDTO(
            '2',
            $product2,
            1,
            false,
            [],
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $items = [$cartItem1, $cartItem2];
        $selectedTotal = $this->calculator->calculateSelectedPrice($items);

        $this->assertEquals('20.00', $selectedTotal);
    }

    public function testApplyDiscount(): void
    {
        $originalPrice = '10.00';

        // Test percentage discount
        $discountedPrice = $this->calculator->applyDiscount($originalPrice, '10', 'percentage');
        $this->assertEquals('9.00', $discountedPrice);

        // Test fixed discount
        $discountedPrice = $this->calculator->applyDiscount($originalPrice, '1.00', 'fixed');
        $this->assertEquals('9.00', $discountedPrice);

        // Test no discount
        $discountedPrice = $this->calculator->applyDiscount($originalPrice, '0', 'percentage');
        $this->assertEquals('10.00', $discountedPrice);
    }

    public function testDiscountDoesNotMakePriceNegative(): void
    {
        $originalPrice = '10.00';

        // Test large percentage discount
        $discountedPrice = $this->calculator->applyDiscount($originalPrice, '150', 'percentage');
        $this->assertEquals('0.00', $discountedPrice);

        // Test large fixed discount
        $discountedPrice = $this->calculator->applyDiscount($originalPrice, '20.00', 'fixed');
        $this->assertEquals('0.00', $discountedPrice);
    }
}
