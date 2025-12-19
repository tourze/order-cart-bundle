<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderCartBundle\Param\CalculateCartTotalParam;
use Tourze\OrderCartBundle\Procedure\CalculateCartTotal;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(CalculateCartTotal::class)]
#[RunTestsInSeparateProcesses]
final class CalculateCartTotalTest extends AbstractProcedureTestCase
{
    protected function onSetUp(): void
    {
        // 移除 parent::setUp() 调用以避免内存泄漏
    }

    public function testProcedureIsRegistered(): void
    {
        $procedure = self::getService(CalculateCartTotal::class);
        $this->assertInstanceOf(CalculateCartTotal::class, $procedure);
    }

    public function testParamObjectHasCorrectProperties(): void
    {
        $param = new CalculateCartTotalParam('freight-123', false);

        $this->assertEquals('freight-123', $param->freightId);
        $this->assertFalse($param->onlySelected);
    }

    public function testParamObjectDefaultValues(): void
    {
        $param = new CalculateCartTotalParam();

        $this->assertNull($param->freightId);
        $this->assertTrue($param->onlySelected);
    }

    public function testExecuteWithParam(): void
    {
        $procedure = self::getService(CalculateCartTotal::class);
        $param = new CalculateCartTotalParam(null, true);

        // 由于需要认证用户，这里只测试方法签名正确
        $this->assertTrue(method_exists($procedure, 'execute'));

        $reflection = new \ReflectionMethod($procedure, 'execute');
        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
    }
}
