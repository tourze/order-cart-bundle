<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\CartOperationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CartOperationException::class)]
final class CartOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testUnsupportedOperation(): void
    {
        $operation = 'unsupported_op';
        $exception = CartOperationException::unsupportedOperation($operation);

        $this->assertInstanceOf(CartOperationException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals("不支持的操作类型: {$operation}", $exception->getMessage());
    }

    public function testEmptyOperation(): void
    {
        $exception = CartOperationException::emptyOperation();

        $this->assertInstanceOf(CartOperationException::class, $exception);
        $this->assertEquals('操作类型不能为空', $exception->getMessage());
    }

    public function testInvalidOperation(): void
    {
        $operation = 'invalid_op';
        $exception = CartOperationException::invalidOperation($operation);

        $this->assertInstanceOf(CartOperationException::class, $exception);
        $this->assertEquals('无效的操作类型: ' . $operation, $exception->getMessage());
    }

    public function testCalculationFailed(): void
    {
        $reason = 'division by zero';
        $exception = CartOperationException::calculationFailed($reason);

        $this->assertInstanceOf(CartOperationException::class, $exception);
        $this->assertEquals('价格计算失败: ' . $reason, $exception->getMessage());
    }
}
