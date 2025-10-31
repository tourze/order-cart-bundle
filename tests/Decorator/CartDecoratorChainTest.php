<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Decorator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Decorator\CartDecoratorChain;
use Tourze\OrderCartBundle\Decorator\CartDecoratorInterface;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;

/**
 * @internal
 */
#[CoversClass(CartDecoratorChain::class)]
final class CartDecoratorChainTest extends TestCase
{
    public function testEmptyDecoratorChain(): void
    {
        $chain = new CartDecoratorChain();
        $user = $this->createMock(UserInterface::class);
        $summary = new CartSummaryDTO(5, 3, '100.00', '75.00');

        $result = $chain->decorate($summary, $user);

        $this->assertSame($summary, $result);
    }

    public function testSingleDecorator(): void
    {
        $chain = new CartDecoratorChain();
        $user = $this->createMock(UserInterface::class);
        $originalSummary = new CartSummaryDTO(5, 3, '100.00', '75.00');
        $decoratedSummary = new CartSummaryDTO(5, 3, '110.00', '82.50');

        $decorator = $this->createMock(CartDecoratorInterface::class);
        $decorator->expects($this->once())
            ->method('decorate')
            ->with($originalSummary, $user)
            ->willReturn($decoratedSummary)
        ;

        $chain->addDecorator($decorator);
        $result = $chain->decorate($originalSummary, $user);

        $this->assertSame($decoratedSummary, $result);
    }

    public function testMultipleDecoratorsWithPriority(): void
    {
        $chain = new CartDecoratorChain();
        $user = $this->createMock(UserInterface::class);
        $originalSummary = new CartSummaryDTO(5, 3, '100.00', '75.00');
        $intermediateState1 = new CartSummaryDTO(5, 3, '110.00', '82.50');
        $intermediateState2 = new CartSummaryDTO(5, 3, '120.00', '90.00');
        $finalSummary = new CartSummaryDTO(5, 3, '130.00', '97.50');

        // 高优先级装饰器 (priority: 10)
        $highPriorityDecorator = $this->createMock(CartDecoratorInterface::class);
        $highPriorityDecorator->method('getPriority')->willReturn(10);
        $highPriorityDecorator->expects($this->once())
            ->method('decorate')
            ->with($originalSummary, $user)
            ->willReturn($intermediateState1)
        ;

        // 中优先级装饰器 (priority: 5)
        $mediumPriorityDecorator = $this->createMock(CartDecoratorInterface::class);
        $mediumPriorityDecorator->method('getPriority')->willReturn(5);
        $mediumPriorityDecorator->expects($this->once())
            ->method('decorate')
            ->with($intermediateState1, $user)
            ->willReturn($intermediateState2)
        ;

        // 低优先级装饰器 (priority: 1)
        $lowPriorityDecorator = $this->createMock(CartDecoratorInterface::class);
        $lowPriorityDecorator->method('getPriority')->willReturn(1);
        $lowPriorityDecorator->expects($this->once())
            ->method('decorate')
            ->with($intermediateState2, $user)
            ->willReturn($finalSummary)
        ;

        // 无序添加装饰器
        $chain->addDecorator($mediumPriorityDecorator);
        $chain->addDecorator($lowPriorityDecorator);
        $chain->addDecorator($highPriorityDecorator);

        $result = $chain->decorate($originalSummary, $user);

        $this->assertSame($finalSummary, $result);
    }

    public function testSamePriorityDecorators(): void
    {
        $chain = new CartDecoratorChain();
        $user = $this->createMock(UserInterface::class);
        $originalSummary = new CartSummaryDTO(2, 1, '50.00', '25.00');
        $firstState = new CartSummaryDTO(2, 1, '55.00', '27.50');
        $secondState = new CartSummaryDTO(2, 1, '60.00', '30.00');

        $decorator1 = $this->createMock(CartDecoratorInterface::class);
        $decorator1->method('getPriority')->willReturn(5);
        $decorator1->expects($this->once())
            ->method('decorate')
            ->with($originalSummary, $user)
            ->willReturn($firstState)
        ;

        $decorator2 = $this->createMock(CartDecoratorInterface::class);
        $decorator2->method('getPriority')->willReturn(5);
        $decorator2->expects($this->once())
            ->method('decorate')
            ->with($firstState, $user)
            ->willReturn($secondState)
        ;

        $chain->addDecorator($decorator1);
        $chain->addDecorator($decorator2);

        $result = $chain->decorate($originalSummary, $user);

        $this->assertSame($secondState, $result);
    }

    public function testDecoratorChainMaintainsOrder(): void
    {
        $chain = new CartDecoratorChain();
        $user = $this->createMock(UserInterface::class);
        $summary = new CartSummaryDTO(1, 1, '10.00', '10.00');

        $executionOrder = [];

        $decorator1 = $this->createMock(CartDecoratorInterface::class);
        $decorator1->method('getPriority')->willReturn(3);
        $decorator1->method('decorate')
            ->willReturnCallback(function ($summary, $user) use (&$executionOrder) {
                $executionOrder[] = 'decorator1';

                return $summary;
            })
        ;

        $decorator2 = $this->createMock(CartDecoratorInterface::class);
        $decorator2->method('getPriority')->willReturn(2);
        $decorator2->method('decorate')
            ->willReturnCallback(function ($summary, $user) use (&$executionOrder) {
                $executionOrder[] = 'decorator2';

                return $summary;
            })
        ;

        $decorator3 = $this->createMock(CartDecoratorInterface::class);
        $decorator3->method('getPriority')->willReturn(1);
        $decorator3->method('decorate')
            ->willReturnCallback(function ($summary, $user) use (&$executionOrder) {
                $executionOrder[] = 'decorator3';

                return $summary;
            })
        ;

        // 无序添加
        $chain->addDecorator($decorator2);
        $chain->addDecorator($decorator3);
        $chain->addDecorator($decorator1);

        $chain->decorate($summary, $user);

        // 应该按优先级从高到低执行
        $this->assertEquals(['decorator1', 'decorator2', 'decorator3'], $executionOrder);
    }

    public function testAddDecorator(): void
    {
        $chain = new CartDecoratorChain();
        $decorator = $this->createMock(CartDecoratorInterface::class);
        $decorator->method('getPriority')->willReturn(5);

        // 测试添加装饰器不抛出异常
        $chain->addDecorator($decorator);

        // 验证装饰器已被添加（通过调用 decorate 方法验证）
        $user = $this->createMock(UserInterface::class);
        $summary = new CartSummaryDTO(1, 1, '10.00', '10.00');

        $decorator->expects($this->once())
            ->method('decorate')
            ->with($summary, $user)
            ->willReturn($summary)
        ;

        $result = $chain->decorate($summary, $user);
        $this->assertSame($summary, $result);
    }

    public function testDecorate(): void
    {
        $chain = new CartDecoratorChain();
        $user = $this->createMock(UserInterface::class);
        $originalSummary = new CartSummaryDTO(2, 1, '20.00', '10.00');
        $decoratedSummary = new CartSummaryDTO(2, 1, '25.00', '12.50');

        $decorator = $this->createMock(CartDecoratorInterface::class);
        $decorator->method('getPriority')->willReturn(1);
        $decorator->expects($this->once())
            ->method('decorate')
            ->with($originalSummary, $user)
            ->willReturn($decoratedSummary)
        ;

        $chain->addDecorator($decorator);
        $result = $chain->decorate($originalSummary, $user);

        $this->assertSame($decoratedSummary, $result);
    }
}
