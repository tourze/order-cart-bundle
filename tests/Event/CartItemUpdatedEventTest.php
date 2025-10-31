<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Event\CartItemUpdatedEvent;

/**
 * @internal
 */
#[CoversClass(CartItemUpdatedEvent::class)]
final class CartItemUpdatedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = $this->createMock(CartItem::class);
        $oldQuantity = 5;
        $newQuantity = 3;

        $event = new CartItemUpdatedEvent($user, $cartItem, $oldQuantity, $newQuantity);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($cartItem, $event->getCartItem());
        $this->assertSame($oldQuantity, $event->getOldQuantity());
        $this->assertSame($newQuantity, $event->getNewQuantity());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getOccurredAt());
    }
}
