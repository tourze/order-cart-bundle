<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Decorator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;

interface CartDecoratorInterface
{
    public function decorate(CartSummaryDTO $summary, UserInterface $user): CartSummaryDTO;

    public function getPriority(): int;
}
