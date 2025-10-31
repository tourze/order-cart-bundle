<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\CartLimitExceededException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CartLimitExceededException::class)]
final class CartLimitExceededExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Cart limit of 10 items exceeded.';
        $exception = new CartLimitExceededException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new CartLimitExceededException('test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
