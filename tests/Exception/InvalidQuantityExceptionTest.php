<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidQuantityException::class)]
final class InvalidQuantityExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Invalid quantity: -5. Quantity must be positive.';
        $exception = new InvalidQuantityException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new InvalidQuantityException('test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
