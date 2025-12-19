<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\RemoveFromCartParam;

/**
 * @internal
 */
#[CoversClass(RemoveFromCartParam::class)]
final class RemoveFromCartParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new RemoveFromCartParam(cartItemId: 'cart-123');

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithValue(): void
    {
        $param = new RemoveFromCartParam(cartItemId: 'cart-456');

        $this->assertSame('cart-456', $param->cartItemId);
    }

    public function testConstructorWithDefaultValue(): void
    {
        $param = new RemoveFromCartParam();

        $this->assertSame('', $param->cartItemId);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RemoveFromCartParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
