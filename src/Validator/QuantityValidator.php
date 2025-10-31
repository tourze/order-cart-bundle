<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Validator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\ProductCoreBundle\Entity\Sku;

final class QuantityValidator implements CartItemValidatorInterface
{
    private const MIN_QUANTITY = 1;
    private const MAX_QUANTITY = 999;

    public function validate(UserInterface $user, Sku $sku, int $quantity): void
    {
        if ($quantity < self::MIN_QUANTITY) {
            throw new InvalidQuantityException(sprintf('Quantity must be at least %d', self::MIN_QUANTITY));
        }

        if ($quantity > self::MAX_QUANTITY) {
            throw new InvalidQuantityException(sprintf('Quantity cannot exceed %d', self::MAX_QUANTITY));
        }
    }

    public function supports(Sku $sku): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        return 100;
    }
}
