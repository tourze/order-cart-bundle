<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\PriceCalculator;

use Tourze\OrderCartBundle\DTO\CartItemDTO;

interface PriceCalculatorInterface
{
    public function calculateItemPrice(CartItemDTO $item): string;

    /**
     * @param array<CartItemDTO> $items
     */
    public function calculateTotalPrice(array $items): string;

    /**
     * @param array<CartItemDTO> $items
     */
    public function calculateSelectedPrice(array $items): string;

    public function applyDiscount(string $price, string $discount, string $type = 'percentage'): string;
}
