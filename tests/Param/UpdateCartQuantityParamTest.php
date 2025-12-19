<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\UpdateCartQuantityParam;

/**
 * @internal
 */
#[CoversClass(UpdateCartQuantityParam::class)]
final class UpdateCartQuantityParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new UpdateCartQuantityParam(
            cartItemId: 'cart-123',
            quantity: 5,
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithAllValues(): void
    {
        $param = new UpdateCartQuantityParam(
            cartItemId: 'cart-456',
            quantity: 10,
        );

        $this->assertSame('cart-456', $param->cartItemId);
        $this->assertSame(10, $param->quantity);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $param = new UpdateCartQuantityParam();

        $this->assertSame('', $param->cartItemId);
        $this->assertSame(1, $param->quantity);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(UpdateCartQuantityParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
