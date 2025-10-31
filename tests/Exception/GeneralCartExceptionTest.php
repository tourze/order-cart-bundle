<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\GeneralCartException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(GeneralCartException::class)]
final class GeneralCartExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new GeneralCartException('Test message');

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals('GENERAL_CART_ERROR', $exception->getErrorCode());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new GeneralCartException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionCanBeCreatedWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new GeneralCartException('Cart error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionCanBeCreatedWithCode(): void
    {
        $exception = new GeneralCartException('Cart error', 500);

        $this->assertEquals(500, $exception->getCode());
    }

    public function testBaseExceptionHasErrorCode(): void
    {
        $exception = new GeneralCartException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertNotEmpty($exception->getErrorCode());
        $this->assertEquals('GENERAL_CART_ERROR', $exception->getErrorCode());
    }
}
