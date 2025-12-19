<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderCartBundle\Param\CalculateCartTotalParam;

/**
 * @internal
 */
#[CoversClass(CalculateCartTotalParam::class)]
final class CalculateCartTotalParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new CalculateCartTotalParam(
            freightId: 'freight-123',
            onlySelected: true,
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithAllValues(): void
    {
        $param = new CalculateCartTotalParam(
            freightId: 'freight-456',
            onlySelected: false,
        );

        $this->assertSame('freight-456', $param->freightId);
        $this->assertFalse($param->onlySelected);
    }

    public function testConstructorWithNullFreightId(): void
    {
        $param = new CalculateCartTotalParam(
            freightId: null,
            onlySelected: true,
        );

        $this->assertNull($param->freightId);
        $this->assertTrue($param->onlySelected);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $param = new CalculateCartTotalParam();

        $this->assertNull($param->freightId);
        $this->assertTrue($param->onlySelected);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(CalculateCartTotalParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
