<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\CheckAllCartItemsParam;

/**
 * @internal
 */
#[CoversClass(CheckAllCartItemsParam::class)]
final class CheckAllCartItemsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new CheckAllCartItemsParam(checked: true);

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithTrue(): void
    {
        $param = new CheckAllCartItemsParam(checked: true);

        $this->assertTrue($param->checked);
    }

    public function testConstructorWithDefaultValue(): void
    {
        $param = new CheckAllCartItemsParam();

        $this->assertFalse($param->checked);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(CheckAllCartItemsParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
