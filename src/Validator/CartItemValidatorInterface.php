<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Validator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Exception\CartException;
use Tourze\ProductCoreBundle\Entity\Sku;

interface CartItemValidatorInterface
{
    /**
     * @throws CartException
     */
    public function validate(UserInterface $user, Sku $sku, int $quantity): void;

    public function supports(Sku $sku): bool;

    public function getPriority(): int;
}
