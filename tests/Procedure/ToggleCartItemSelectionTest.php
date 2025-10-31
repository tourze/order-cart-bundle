<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Procedure\ToggleCartItemSelection;

/**
 * @internal
 */
#[CoversClass(ToggleCartItemSelection::class)]
#[RunTestsInSeparateProcesses]
final class ToggleCartItemSelectionTest extends AbstractProcedureTestCase
{
    private ToggleCartItemSelection $procedure;

    protected function onSetUp(): void
    {
        // 直接使用服务容器获取真实的服务实例
        $this->procedure = self::getService(ToggleCartItemSelection::class);
    }

    public function testExecuteWithEmptyCartItemIdsShouldThrowException(): void
    {
        $this->procedure->cartItemIds = '';
        $this->procedure->selected = true;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithEmptyArrayCartItemIdsShouldThrowException(): void
    {
        $this->procedure->cartItemIds = [];
        $this->procedure->selected = true;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithTooManyItemsShouldThrowException(): void
    {
        // 创建201个商品ID（超过限制的200个）
        $tooManyIds = [];
        for ($i = 1; $i <= 201; ++$i) {
            $tooManyIds[] = "item-{$i}";
        }
        $this->procedure->cartItemIds = $tooManyIds;
        $this->procedure->selected = true;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithInvalidItemIdShouldThrowException(): void
    {
        $this->procedure->cartItemIds = ['valid-id', '', 'another-valid-id'];
        $this->procedure->selected = true;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithWhitespaceItemIdShouldThrowException(): void
    {
        $this->procedure->cartItemIds = ['valid-id', '   ', 'another-valid-id'];
        $this->procedure->selected = true;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithValidSingleItemShouldNotThrowException(): void
    {
        $this->procedure->cartItemIds = 'valid-cart-item-123';
        $this->procedure->selected = true;

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithValidMultipleItemsShouldNotThrowException(): void
    {
        $this->procedure->cartItemIds = ['item1', 'item2', 'item3'];
        $this->procedure->selected = false;

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMaximumAllowedItemsShouldNotThrowException(): void
    {
        // 创建200个商品ID（正好在限制内）
        $maxIds = [];
        for ($i = 1; $i <= 200; ++$i) {
            $maxIds[] = "item-{$i}";
        }
        $this->procedure->cartItemIds = $maxIds;
        $this->procedure->selected = true;

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithUnauthenticatedUserShouldReturnFailure(): void
    {
        // 确保没有用户登录
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $this->procedure->cartItemIds = 'cart-item-123';
        $this->procedure->selected = true;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }

    public function testGetMockResultShouldReturnExpectedStructure(): void
    {
        $result = ToggleCartItemSelection::getMockResult();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertIsArray($result['updated']);
    }
}
