<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Decorator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;

final class CartDecoratorChain
{
    /**
     * @var array<CartDecoratorInterface>
     */
    private array $decorators = [];

    public function addDecorator(CartDecoratorInterface $decorator): void
    {
        $this->decorators[] = $decorator;
    }

    public function decorate(CartSummaryDTO $summary, UserInterface $user): CartSummaryDTO
    {
        $sortedDecorators = $this->getSortedDecorators();

        foreach ($sortedDecorators as $decorator) {
            $summary = $decorator->decorate($summary, $user);
        }

        return $summary;
    }

    /**
     * @return array<CartDecoratorInterface>
     */
    private function getSortedDecorators(): array
    {
        $decorators = $this->decorators;

        usort($decorators, function (CartDecoratorInterface $a, CartDecoratorInterface $b): int {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $decorators;
    }
}
