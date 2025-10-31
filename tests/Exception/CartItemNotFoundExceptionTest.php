<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\CartItemNotFoundException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CartItemNotFoundException::class)]
final class CartItemNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = "Cart item with ID 'test-id' was not found.";
        $exception = new CartItemNotFoundException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new CartItemNotFoundException('test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
