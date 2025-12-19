<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\OrderCartBundle\Param\CheckAllCartItemsParam;
use Tourze\OrderCartBundle\Procedure\CheckAllCartItems;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(CheckAllCartItems::class)]
#[RunTestsInSeparateProcesses]
final class CheckAllCartItemsTest extends AbstractProcedureTestCase
{
    private CheckAllCartItems $procedure;

    protected function onSetUp(): void
    {
        // 直接使用服务容器获取真实的服务实例
        $this->procedure = self::getService(CheckAllCartItems::class);
    }

    public function testParamObjectWithTrueValue(): void
    {
        $param = new CheckAllCartItemsParam(true);
        $this->assertTrue($param->checked);
    }

    public function testParamObjectWithFalseValue(): void
    {
        $param = new CheckAllCartItemsParam(false);
        $this->assertFalse($param->checked);
    }

    public function testParamObjectWithDefaultValue(): void
    {
        $param = new CheckAllCartItemsParam();
        $this->assertFalse($param->checked);
    }

    public function testExecuteWithUnauthenticatedUserShouldReturnFailure(): void
    {
        // 确保没有用户登录
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $param = new CheckAllCartItemsParam(true);

        $result = $this->procedure->execute($param);

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }

    public function testExecuteWithUnauthenticatedUserAndFalseCheckedShouldReturnFailure(): void
    {
        // 确保没有用户登录
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $param = new CheckAllCartItemsParam(false);

        $result = $this->procedure->execute($param);

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }
}
