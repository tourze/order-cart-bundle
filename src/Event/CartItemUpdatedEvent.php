<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;

final class CartItemUpdatedEvent
{
    private \DateTimeInterface $occurredAt;

    public function __construct(
        private readonly UserInterface $user,
        private readonly CartItem $cartItem,
        private readonly int $oldQuantity,
        private readonly int $newQuantity,
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

    public function getOldQuantity(): int
    {
        return $this->oldQuantity;
    }

    public function getNewQuantity(): int
    {
        return $this->newQuantity;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }
}
