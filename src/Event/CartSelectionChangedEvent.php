<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;

final class CartSelectionChangedEvent
{
    private \DateTimeInterface $occurredAt;

    public function __construct(
        private readonly UserInterface $user,
        private readonly CartItem $cartItem,
        private readonly bool $selected,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getCartItem(): CartItem
    {
        return $this->cartItem;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }
}
