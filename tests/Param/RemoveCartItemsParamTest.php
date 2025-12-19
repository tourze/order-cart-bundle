<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\RemoveCartItemsParam;

/**
 * @internal
 */
#[CoversClass(RemoveCartItemsParam::class)]
final class RemoveCartItemsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new RemoveCartItemsParam(itemIds: ['item1', 'item2']);

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithValue(): void
    {
        $param = new RemoveCartItemsParam(itemIds: ['item1', 'item2', 'item3']);

        $this->assertSame(['item1', 'item2', 'item3'], $param->itemIds);
    }

    public function testConstructorWithDefaultValue(): void
    {
        $param = new RemoveCartItemsParam();

        $this->assertSame([], $param->itemIds);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RemoveCartItemsParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
