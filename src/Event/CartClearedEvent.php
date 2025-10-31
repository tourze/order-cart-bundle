<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;

final class CartClearedEvent
{
    private \DateTimeInterface $occurredAt;

    public function __construct(
        private readonly UserInterface $user,
        private readonly int $itemCount,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }
}
