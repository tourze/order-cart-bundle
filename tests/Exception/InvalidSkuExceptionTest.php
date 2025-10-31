<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidSkuException::class)]
final class InvalidSkuExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Invalid SKU: INVALID-SKU';
        $exception = new InvalidSkuException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new InvalidSkuException('test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
