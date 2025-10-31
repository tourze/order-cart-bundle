<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\InvalidArgumentException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Invalid argument provided';
        $exception = new InvalidArgumentException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new InvalidArgumentException('test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
