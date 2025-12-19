<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Param\RemoveCartItemsParam;
use Tourze\OrderCartBundle\Procedure\RemoveCartItems;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(RemoveCartItems::class)]
#[RunTestsInSeparateProcesses]
final class RemoveCartItemsTest extends AbstractProcedureTestCase
{
    private RemoveCartItems $procedure;

    protected function onSetUp(): void
    {
        // 直接使用服务容器获取真实的服务实例
        $this->procedure = self::getService(RemoveCartItems::class);
    }

    public function testExecuteWithEmptyItemIdsShouldThrowException(): void
    {
        $param = new RemoveCartItemsParam([]);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithTooManyItemsShouldThrowException(): void
    {
        $param = new RemoveCartItemsParam(array_fill(0, 201, 'item_id'));

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithInvalidItemIdShouldThrowException(): void
    {
        $param = new RemoveCartItemsParam(['item1', '', 'item3']);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithDuplicateItemIdsShouldThrowException(): void
    {
        $param = new RemoveCartItemsParam(['item1', 'item2', 'item1']);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithValidSingleItemShouldNotThrowException(): void
    {
        $param = new RemoveCartItemsParam(['valid-cart-item-123']);

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithValidMultipleItemsShouldNotThrowException(): void
    {
        $param = new RemoveCartItemsParam(['item1', 'item2', 'item3']);

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
        $param = new RemoveCartItemsParam($maxIds);

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

        $param = new RemoveCartItemsParam(['cart-item-123']);

        $result = $this->procedure->execute($param);

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }
}
