<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Validator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\ProductCoreBundle\Entity\Sku;

final class CartItemValidatorChain
{
    /**
     * @var array<CartItemValidatorInterface>
     */
    private array $validators = [];

    public function addValidator(CartItemValidatorInterface $validator): void
    {
        $this->validators[] = $validator;
    }

    public function validate(UserInterface $user, Sku $sku, int $quantity): void
    {
        $sortedValidators = $this->getSortedValidators();

        foreach ($sortedValidators as $validator) {
            if ($validator->supports($sku)) {
                $validator->validate($user, $sku, $quantity);
            }
        }
    }

    public function supports(Sku $sku): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->supports($sku)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<CartItemValidatorInterface>
     */
    private function getSortedValidators(): array
    {
        $validators = $this->validators;

        usort($validators, function (CartItemValidatorInterface $a, CartItemValidatorInterface $b): int {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $validators;
    }
}
