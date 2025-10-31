<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Event\CartItemAddedEvent;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(CartItemAddedEvent::class)]
final class CartItemAddedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = $this->createMock(CartItem::class);
        $context = ['source' => 'api', 'ip' => '127.0.0.1'];

        $event = new CartItemAddedEvent($user, $cartItem, $context);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($cartItem, $event->getCartItem());
        $this->assertSame($context, $event->getContext());
    }

    public function testConstructorWithoutContext(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = $this->createMock(CartItem::class);

        $event = new CartItemAddedEvent($user, $cartItem);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($cartItem, $event->getCartItem());
        $this->assertSame([], $event->getContext());
    }
}
