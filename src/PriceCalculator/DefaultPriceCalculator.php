<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\PriceCalculator;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Tourze\OrderCartBundle\DTO\CartItemDTO;

#[AsAlias(id: PriceCalculatorInterface::class, public: true)]
final class DefaultPriceCalculator implements PriceCalculatorInterface
{
    public function calculateItemPrice(CartItemDTO $item): string
    {
        return bcmul((string) (float) $item->getProduct()->getPrice(), (string) $item->getQuantity(), 2);
    }

    public function calculateTotalPrice(array $items): string
    {
        $total = '0.00';

        foreach ($items as $item) {
            $total = bcadd($total, (string) (float) $this->calculateItemPrice($item), 2);
        }

        return $total;
    }

    public function calculateSelectedPrice(array $items): string
    {
        $total = '0.00';

        foreach ($items as $item) {
            if ($item->isSelected()) {
                $total = bcadd($total, (string) (float) $this->calculateItemPrice($item), 2);
            }
        }

        return $total;
    }

    public function applyDiscount(string $price, string $discount, string $type = 'percentage'): string
    {
        if (bccomp((string) (float) $discount, '0.00', 2) <= 0) {
            return $price;
        }

        if ('percentage' === $type) {
            // 计算折扣金额: price * (discount / 100)
            $discountAmount = bcmul((string) (float) $price, bcdiv((string) (float) $discount, '100', 4), 2);
            $discountedPrice = bcsub((string) (float) $price, (string) (float) $discountAmount, 2);
        } elseif ('fixed' === $type) {
            $discountedPrice = bcsub((string) (float) $price, (string) (float) $discount, 2);
        } else {
            $discountedPrice = $price;
        }

        // 确保价格不为负数
        return bccomp((string) (float) $discountedPrice, '0.00', 2) < 0 ? '0.00' : $discountedPrice;
    }
}
