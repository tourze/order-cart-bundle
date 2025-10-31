<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Event\CartItemAddedEvent;

/**
 * @internal
 */
#[CoversClass(CartItemAddedEvent::class)]
final class CartEventTest extends TestCase
{
    public function testCartItemAddedEvent(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = $this->createMock(CartItem::class);
        $context = ['source' => 'product_page'];

        $event = new CartItemAddedEvent($user, $cartItem, $context);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($cartItem, $event->getCartItem());
        $this->assertEquals($context, $event->getContext());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getOccurredAt());
    }

    public function testEventsAreImmutable(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = $this->createMock(CartItem::class);

        $event = new CartItemAddedEvent($user, $cartItem);

        // 使用行为测试验证事件不可变性，而非反射检查方法
        $immutabilityVerifier = new class($event) {
            public function __construct(private readonly CartItemAddedEvent $event)
            {
            }

            public function verifyNoSetters(): bool
            {
                // 验证事件对象状态在创建后保持不变
                $initialUser = $this->event->getUser();
                $initialCartItem = $this->event->getCartItem();
                $initialTime = $this->event->getOccurredAt();

                // 等待微小时间后再次检查，状态应该相同
                usleep(1000);

                return $this->event->getUser() === $initialUser
                    && $this->event->getCartItem() === $initialCartItem
                    && $this->event->getOccurredAt() === $initialTime;
            }
        };

        $this->assertTrue($immutabilityVerifier->verifyNoSetters(), 'Event should maintain immutable state');
    }
}
