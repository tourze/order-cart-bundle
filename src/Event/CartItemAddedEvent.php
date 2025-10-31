<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;

final class CartItemAddedEvent
{
    private \DateTimeInterface $occurredAt;

    public function __construct(
        private readonly UserInterface $user,
        private readonly CartItem $cartItem,
        /** @var array<string, mixed> */
        private readonly array $context = [],
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

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }
}
