<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Param\UpdateCartQuantityParam;
use Tourze\OrderCartBundle\Procedure\UpdateCartQuantity;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

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
        $param = new UpdateCartQuantityParam('cart-item-123', 5);

        // 使用反射测试验证方法
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMinimumQuantityShouldValidate(): void
    {
        $param = new UpdateCartQuantityParam('cart-item-123', 1);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMaximumQuantityShouldValidate(): void
    {
        $param = new UpdateCartQuantityParam('cart-item-123', 999);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithEmptyCartItemIdShouldThrowException(): void
    {
        $param = new UpdateCartQuantityParam('', 2);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithWhitespaceCartItemIdShouldThrowException(): void
    {
        $param = new UpdateCartQuantityParam('   ', 2);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithZeroQuantityShouldThrowException(): void
    {
        $param = new UpdateCartQuantityParam('cart-item-123', 0);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithNegativeQuantityShouldThrowException(): void
    {
        $param = new UpdateCartQuantityParam('cart-item-123', -1);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithExcessiveQuantityShouldThrowException(): void
    {
        $param = new UpdateCartQuantityParam('cart-item-123', 1000);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithLongCartItemIdShouldValidate(): void
    {
        $longId = str_repeat('a', 255);
        $param = new UpdateCartQuantityParam($longId, 2);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithSpecialCharactersInCartItemIdShouldValidate(): void
    {
        $specialId = 'cart-item-123-αβγ-émojì-测试';
        $param = new UpdateCartQuantityParam($specialId, 3);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);

        // 应该不抛出异常
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }
}
