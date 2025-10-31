<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Event\CartItemRemovedEvent;

/**
 * @internal
 */
#[CoversClass(CartItemRemovedEvent::class)]
final class CartItemRemovedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItemId = 'cart_item_123';
        $skuId = 'sku_456';

        $event = new CartItemRemovedEvent($user, $cartItemId, $skuId);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($cartItemId, $event->getCartItemId());
        $this->assertSame($skuId, $event->getSkuId());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getOccurredAt());
    }
}
