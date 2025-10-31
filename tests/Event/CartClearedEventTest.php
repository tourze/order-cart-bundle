<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Event\CartClearedEvent;

/**
 * @internal
 */
#[CoversClass(CartClearedEvent::class)]
final class CartClearedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('test@example.com');

        $event = new CartClearedEvent($user, 5);

        $this->assertSame($user, $event->getUser());
    }

    public function testEventName(): void
    {
        $user = $this->createMock(UserInterface::class);
        $event = new CartClearedEvent($user, 5);

        $this->assertInstanceOf(CartClearedEvent::class, $event);
    }
}
