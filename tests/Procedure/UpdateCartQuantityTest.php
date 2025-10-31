<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Procedure\UpdateCartQuantity;

/**
 * @internal
 */
#[CoversClass(UpdateCartQuantity::class)]
#[RunTestsInSeparateProcesses]
final class UpdateCartQuantityTest extends AbstractProcedureTestCase
{
    private UpdateCartQuantity $procedure;

    protected function onSetUp(): void
    {
        // 直接从容器获取服务实例
        $this->procedure = self::getService(UpdateCartQuantity::class);
    }

    public function testExecuteWithValidQuantityShouldValidateSuccessfully(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';
        $this->procedure->quantity = 5;

        // 使用反射测试验证方法
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMinimumQuantityShouldValidate(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';
        $this->procedure->quantity = 1;

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMaximumQuantityShouldValidate(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';
        $this->procedure->quantity = 999;

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithEmptyCartItemIdShouldThrowException(): void
    {
        $this->procedure->cartItemId = '';
        $this->procedure->quantity = 2;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithWhitespaceCartItemIdShouldThrowException(): void
    {
        $this->procedure->cartItemId = '   ';
        $this->procedure->quantity = 2;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithZeroQuantityShouldThrowException(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';
        $this->procedure->quantity = 0;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithNegativeQuantityShouldThrowException(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';
        $this->procedure->quantity = -1;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithExcessiveQuantityShouldThrowException(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';
        $this->procedure->quantity = 1000;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithLongCartItemIdShouldValidate(): void
    {
        $longId = str_repeat('a', 255);
        $this->procedure->cartItemId = $longId;
        $this->procedure->quantity = 2;

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithSpecialCharactersInCartItemIdShouldValidate(): void
    {
        $specialId = 'cart-item-123-αβγ-émojì-测试';
        $this->procedure->cartItemId = $specialId;
        $this->procedure->quantity = 3;

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }
}
