<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Event\PriceChangeEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(PriceChangeEvent::class)]
final class PriceChangeEventTest extends AbstractEventTestCase
{
    private Sku $sku;

    protected function setUp(): void
    {
        $this->sku = $this->createMock(Sku::class);
        $this->sku->method('getId')->willReturn('test-sku-123');
    }

    public function testConstructorShouldSetProperties(): void
    {
        $oldPrice = '100.00';
        $newPrice = '120.00';
        $priceChanges = [
            'sku123' => ['oldPrice' => '100.00', 'newPrice' => '120.00'],
        ];

        $event = new PriceChangeEvent($this->sku, $oldPrice, $newPrice, $priceChanges);

        $this->assertSame($this->sku, $event->getSku());
        $this->assertEquals('100.00', $event->getOldPrice());
        $this->assertEquals('120.00', $event->getNewPrice());
        $this->assertEquals($priceChanges, $event->getPriceChanges());
    }

    public function testGetPriceChangeAmountWithIncrease(): void
    {
        $event = new PriceChangeEvent($this->sku, '100.00', '120.50');

        $this->assertEquals('20.50', $event->getPriceChangeAmount());
    }

    public function testGetPriceChangeAmountWithDecrease(): void
    {
        $event = new PriceChangeEvent($this->sku, '100.00', '85.25');

        $this->assertEquals('-14.75', $event->getPriceChangeAmount());
    }

    public function testIsPriceIncrease(): void
    {
        $increaseEvent = new PriceChangeEvent($this->sku, '100.00', '120.00');
        $decreaseEvent = new PriceChangeEvent($this->sku, '100.00', '80.00');
        $sameEvent = new PriceChangeEvent($this->sku, '100.00', '100.00');

        $this->assertTrue($increaseEvent->isPriceIncrease());
        $this->assertFalse($decreaseEvent->isPriceIncrease());
        $this->assertFalse($sameEvent->isPriceIncrease());
    }

    public function testIsPriceDecrease(): void
    {
        $increaseEvent = new PriceChangeEvent($this->sku, '100.00', '120.00');
        $decreaseEvent = new PriceChangeEvent($this->sku, '100.00', '80.00');
        $sameEvent = new PriceChangeEvent($this->sku, '100.00', '100.00');

        $this->assertFalse($increaseEvent->isPriceDecrease());
        $this->assertTrue($decreaseEvent->isPriceDecrease());
        $this->assertFalse($sameEvent->isPriceDecrease());
    }

    public function testGetPriceChangePercentageWithIncrease(): void
    {
        $event = new PriceChangeEvent($this->sku, '100.00', '125.00');

        $this->assertEquals('25.00', $event->getPriceChangePercentage());
    }

    public function testGetPriceChangePercentageWithDecrease(): void
    {
        $event = new PriceChangeEvent($this->sku, '100.00', '80.00');

        $this->assertEquals('-20.00', $event->getPriceChangePercentage());
    }

    public function testGetPriceChangePercentageWithZeroOldPrice(): void
    {
        $event = new PriceChangeEvent($this->sku, '0.00', '50.00');

        $this->assertEquals('0.00', $event->getPriceChangePercentage());
    }

    public function testGetPriceChangePercentageWithEmptyOldPrice(): void
    {
        $event = new PriceChangeEvent($this->sku, '0', '50.00');

        $this->assertEquals('0.00', $event->getPriceChangePercentage());
    }

    public function testConstantName(): void
    {
        $this->assertEquals('cart.price_change', PriceChangeEvent::NAME);
    }

    public function testEventWithEmptyPriceChanges(): void
    {
        $event = new PriceChangeEvent($this->sku, '100.00', '110.00');

        $this->assertEquals([], $event->getPriceChanges());
    }
}
