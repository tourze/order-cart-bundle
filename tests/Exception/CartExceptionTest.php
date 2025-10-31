<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\CartException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CartException::class)]
final class CartExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInterface(): void
    {
        $reflection = new \ReflectionClass(CartException::class);

        $this->assertTrue($reflection->isAbstract(), 'CartException should be an abstract class');
        $this->assertTrue($reflection->isSubclassOf(\Exception::class), 'CartException should extend Exception');
    }

    public function testExceptionHasErrorCodeMethod(): void
    {
        $reflection = new \ReflectionClass(CartException::class);

        $this->assertTrue($reflection->hasMethod('getErrorCode'), 'CartException should have getErrorCode method');
    }
}
