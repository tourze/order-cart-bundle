<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Param\SetCartItemCheckedParam;
use Tourze\OrderCartBundle\Procedure\SetCartItemChecked;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(SetCartItemChecked::class)]
#[RunTestsInSeparateProcesses]
final class SetCartItemCheckedTest extends AbstractProcedureTestCase
{
    private SetCartItemChecked $procedure;

    protected function onSetUp(): void
    {
        // 直接使用服务容器获取真实的服务实例
        $this->procedure = self::getService(SetCartItemChecked::class);
    }

    public function testExecuteWithEmptyItemIdsShouldThrowException(): void
    {
        $param = new SetCartItemCheckedParam([], true);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithTooManyItemsShouldThrowException(): void
    {
        $param = new SetCartItemCheckedParam(array_fill(0, 201, 'item_id'), true);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithInvalidItemIdShouldThrowException(): void
    {
        $param = new SetCartItemCheckedParam(['item1', '', 'item3'], true);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithDuplicateItemIdsShouldThrowException(): void
    {
        $param = new SetCartItemCheckedParam(['item1', 'item2', 'item1'], true);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithValidSingleItemShouldNotThrowException(): void
    {
        $param = new SetCartItemCheckedParam(['valid-cart-item-123'], true);

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithValidMultipleItemsShouldNotThrowException(): void
    {
        $param = new SetCartItemCheckedParam(['item1', 'item2', 'item3'], false);

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMaximumAllowedItemsShouldNotThrowException(): void
    {
        // 创建200个商品ID（正好在限制内）
        $maxIds = [];
        for ($i = 1; $i <= 200; ++$i) {
            $maxIds[] = "item-{$i}";
        }
        $param = new SetCartItemCheckedParam($maxIds, true);

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithUnauthenticatedUserShouldReturnFailure(): void
    {
        // 确保没有用户登录
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $param = new SetCartItemCheckedParam(['cart-item-123'], true);

        $result = $this->procedure->execute($param);

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }
}
