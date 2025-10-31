<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Event\CartSelectionChangedEvent;

/**
 * @internal
 */
#[CoversClass(CartSelectionChangedEvent::class)]
final class CartSelectionChangedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = $this->createMock(CartItem::class);
        $selected = true;

        $event = new CartSelectionChangedEvent($user, $cartItem, $selected);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($cartItem, $event->getCartItem());
        $this->assertSame($selected, $event->isSelected());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getOccurredAt());
    }
}
