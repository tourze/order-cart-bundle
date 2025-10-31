<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\OrderCartBundle\Validator\CartItemValidatorChain;
use Tourze\OrderCartBundle\Validator\CartItemValidatorInterface;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(CartItemValidatorChain::class)]
final class CartItemValidatorChainTest extends TestCase
{
    private CartItemValidatorChain $chain;

    protected function setUp(): void
    {
        $this->chain = new CartItemValidatorChain();
    }

    public function testValidateWithNoValidators(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        // Should not throw any exception - this expectation is implicit
        $this->chain->validate($user, $sku, 1);

        // If we reach here, validation passed without exception
        $this->expectNotToPerformAssertions();
    }

    public function testValidateWithSingleValidator(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $validator = $this->createMock(CartItemValidatorInterface::class);
        $validator->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;
        $validator->expects($this->once())
            ->method('validate')
            ->with($user, $sku, 1)
        ;

        $this->chain->addValidator($validator);
        $this->chain->validate($user, $sku, 1);
    }

    public function testValidateWithMultipleValidatorsInPriorityOrder(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $executionOrder = [];

        $validator1 = $this->createMock(CartItemValidatorInterface::class);
        $validator1->expects($this->any())
            ->method('getPriority')
            ->willReturn(10)
        ;
        $validator1->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;
        $validator1->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function () use (&$executionOrder): void {
                $executionOrder[] = 'validator1';
            })
        ;

        $validator2 = $this->createMock(CartItemValidatorInterface::class);
        $validator2->expects($this->any())
            ->method('getPriority')
            ->willReturn(20)
        ;
        $validator2->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;
        $validator2->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function () use (&$executionOrder): void {
                $executionOrder[] = 'validator2';
            })
        ;

        $validator3 = $this->createMock(CartItemValidatorInterface::class);
        $validator3->expects($this->any())
            ->method('getPriority')
            ->willReturn(5)
        ;
        $validator3->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;
        $validator3->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function () use (&$executionOrder): void {
                $executionOrder[] = 'validator3';
            })
        ;

        $this->chain->addValidator($validator1);
        $this->chain->addValidator($validator2);
        $this->chain->addValidator($validator3);

        $this->chain->validate($user, $sku, 1);

        // Higher priority executes first
        $this->assertEquals(['validator2', 'validator1', 'validator3'], $executionOrder);
    }

    public function testValidationStopsOnFirstException(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $validator1 = $this->createMock(CartItemValidatorInterface::class);
        $validator1->expects($this->any())
            ->method('getPriority')
            ->willReturn(10)
        ;
        $validator1->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;
        $validator1->expects($this->once())
            ->method('validate')
            ->willThrowException(new InvalidQuantityException('Invalid quantity'))
        ;

        $validator2 = $this->createMock(CartItemValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('getPriority')
            ->willReturn(5)
        ;
        $validator2->expects($this->never())
            ->method('validate')
        ;

        $this->chain->addValidator($validator1);
        $this->chain->addValidator($validator2);

        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Invalid quantity');

        $this->chain->validate($user, $sku, 1);
    }

    public function testSupportsMethodDelegation(): void
    {
        $sku = $this->createMock(Sku::class);

        $validator1 = $this->createMock(CartItemValidatorInterface::class);
        $validator1->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(false)
        ;

        $validator2 = $this->createMock(CartItemValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;

        $this->chain->addValidator($validator1);
        $this->chain->addValidator($validator2);

        $this->assertTrue($this->chain->supports($sku));
    }

    public function testOnlyCallsSupportingValidators(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $validator1 = $this->createMock(CartItemValidatorInterface::class);
        $validator1->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(false)
        ;
        $validator1->expects($this->never())
            ->method('validate')
        ;

        $validator2 = $this->createMock(CartItemValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('supports')
            ->with($sku)
            ->willReturn(true)
        ;
        $validator2->expects($this->once())
            ->method('validate')
            ->with($user, $sku, 1)
        ;

        $this->chain->addValidator($validator1);
        $this->chain->addValidator($validator2);

        $this->chain->validate($user, $sku, 1);
    }

    public function testAddValidator(): void
    {
        $validator = $this->createMock(CartItemValidatorInterface::class);
        $validator->method('supports')->willReturn(true);
        $validator->expects($this->once())->method('validate');

        // 测试添加验证器不抛出异常
        $this->chain->addValidator($validator);

        // 验证验证器已被添加（通过调用 validate 方法验证）
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $this->chain->validate($user, $sku, 1);
    }
}
