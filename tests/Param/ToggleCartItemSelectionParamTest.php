<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\ToggleCartItemSelectionParam;

/**
 * @internal
 */
#[CoversClass(ToggleCartItemSelectionParam::class)]
final class ToggleCartItemSelectionParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new ToggleCartItemSelectionParam(
            cartItemIds: 'item1',
            selected: true,
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithStringValue(): void
    {
        $param = new ToggleCartItemSelectionParam(
            cartItemIds: 'item123',
            selected: true,
        );

        $this->assertSame('item123', $param->cartItemIds);
        $this->assertTrue($param->selected);
    }

    public function testConstructorWithArrayValue(): void
    {
        $param = new ToggleCartItemSelectionParam(
            cartItemIds: ['item1', 'item2', 'item3'],
            selected: false,
        );

        $this->assertSame(['item1', 'item2', 'item3'], $param->cartItemIds);
        $this->assertFalse($param->selected);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $param = new ToggleCartItemSelectionParam();

        $this->assertSame('', $param->cartItemIds);
        $this->assertTrue($param->selected);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(ToggleCartItemSelectionParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
