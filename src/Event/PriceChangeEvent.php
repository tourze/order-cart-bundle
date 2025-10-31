<?php

namespace Tourze\OrderCartBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\ProductCoreBundle\Entity\Sku;

final class PriceChangeEvent extends Event
{
    public const NAME = 'cart.price_change';

    /**
     * @param array<string, array{oldPrice: string, newPrice: string}> $priceChanges
     */
    public function __construct(
        private readonly Sku $sku,
        private readonly string $oldPrice,
        private readonly string $newPrice,
        private readonly array $priceChanges = [],
    ) {
    }

    public function getSku(): Sku
    {
        return $this->sku;
    }

    public function getOldPrice(): string
    {
        return $this->oldPrice;
    }

    public function getNewPrice(): string
    {
        return $this->newPrice;
    }

    /**
     * @return array<string, array{oldPrice: string, newPrice: string}>
     */
    public function getPriceChanges(): array
    {
        return $this->priceChanges;
    }

    public function getPriceChangeAmount(): string
    {
        $change = (float) $this->newPrice - (float) $this->oldPrice;

        return number_format($change, 2, '.', '');
    }

    public function isPriceIncrease(): bool
    {
        return (float) $this->newPrice > (float) $this->oldPrice;
    }

    public function isPriceDecrease(): bool
    {
        return (float) $this->newPrice < (float) $this->oldPrice;
    }

    public function getPriceChangePercentage(): string
    {
        if ('0' === $this->oldPrice || '0.00' === $this->oldPrice) {
            return '0.00';
        }

        // 使用 BCMath 计算百分比: (newPrice - oldPrice) / oldPrice * 100
        $difference = bcsub((string) (float) $this->newPrice, (string) (float) $this->oldPrice, 4);

        return bcmul(bcdiv($difference, (string) (float) $this->oldPrice, 4), '100', 2);
    }
}
