<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\AddToCartParam;

/**
 * @internal
 */
#[CoversClass(AddToCartParam::class)]
final class AddToCartParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new AddToCartParam(
            skuId: '123',
            quantity: 2,
            metadata: ['color' => 'red'],
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithAllValues(): void
    {
        $param = new AddToCartParam(
            skuId: '123',
            quantity: 5,
            metadata: ['color' => 'blue', 'size' => 'L'],
        );

        $this->assertSame('123', $param->skuId);
        $this->assertSame(5, $param->quantity);
        $this->assertSame(['color' => 'blue', 'size' => 'L'], $param->metadata);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $param = new AddToCartParam(
            skuId: '456',
        );

        $this->assertSame('456', $param->skuId);
        $this->assertSame(1, $param->quantity);
        $this->assertSame([], $param->metadata);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(AddToCartParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
