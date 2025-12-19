<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\SetCartItemCheckedParam;

/**
 * @internal
 */
#[CoversClass(SetCartItemCheckedParam::class)]
final class SetCartItemCheckedParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new SetCartItemCheckedParam(
            itemIds: ['item1', 'item2'],
            checked: true,
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithAllValues(): void
    {
        $param = new SetCartItemCheckedParam(
            itemIds: ['item1', 'item2', 'item3'],
            checked: true,
        );

        $this->assertSame(['item1', 'item2', 'item3'], $param->itemIds);
        $this->assertTrue($param->checked);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $param = new SetCartItemCheckedParam();

        $this->assertSame([], $param->itemIds);
        $this->assertFalse($param->checked);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(SetCartItemCheckedParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
