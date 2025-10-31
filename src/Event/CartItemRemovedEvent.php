<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;

final class CartItemRemovedEvent
{
    private \DateTimeInterface $occurredAt;

    public function __construct(
        private readonly UserInterface $user,
        private readonly string $cartItemId,
        private readonly string $skuId,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getCartItemId(): string
    {
        return $this->cartItemId;
    }

    public function getSkuId(): string
    {
        return $this->skuId;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }
}
