<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\GetCartListParam;

/**
 * @internal
 */
#[CoversClass(GetCartListParam::class)]
final class GetCartListParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new GetCartListParam(selectedOnly: true);

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithTrue(): void
    {
        $param = new GetCartListParam(selectedOnly: true);

        $this->assertTrue($param->selectedOnly);
    }

    public function testConstructorWithFalse(): void
    {
        $param = new GetCartListParam(selectedOnly: false);

        $this->assertFalse($param->selectedOnly);
    }

    public function testConstructorWithDefaultValue(): void
    {
        $param = new GetCartListParam();

        $this->assertFalse($param->selectedOnly);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(GetCartListParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
