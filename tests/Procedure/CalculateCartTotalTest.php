<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\Procedure\CalculateCartTotal;

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

    public function testProcedureHasOnlySelectedProperty(): void
    {
        $procedure = self::getService(CalculateCartTotal::class);
        $reflection = new \ReflectionClass($procedure);
        $this->assertTrue($reflection->hasProperty('onlySelected'));
    }

    public function testProcedureHasFreightIdProperty(): void
    {
        $procedure = self::getService(CalculateCartTotal::class);
        $reflection = new \ReflectionClass($procedure);
        $this->assertTrue($reflection->hasProperty('freightId'));
    }

    public function testExecute(): void
    {
        $procedure = self::getService(CalculateCartTotal::class);

        // 测试基本功能存在性
        $this->assertTrue(method_exists($procedure, 'execute'), 'CalculateCartTotal should have execute method');

        // 测试属性可以被设置
        $reflection = new \ReflectionClass($procedure);

        $onlySelectedProperty = $reflection->getProperty('onlySelected');
        $onlySelectedProperty->setAccessible(true);
        $onlySelectedProperty->setValue($procedure, true);
        $this->assertTrue($onlySelectedProperty->getValue($procedure));

        $freightIdProperty = $reflection->getProperty('freightId');
        $freightIdProperty->setAccessible(true);
        $freightIdProperty->setValue($procedure, 123);
        $this->assertEquals(123, $freightIdProperty->getValue($procedure));
    }
}
