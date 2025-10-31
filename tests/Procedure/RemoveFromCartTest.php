<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Procedure\RemoveFromCart;

/**
 * @internal
 */
#[CoversClass(RemoveFromCart::class)]
#[RunTestsInSeparateProcesses]
final class RemoveFromCartTest extends AbstractProcedureTestCase
{
    private RemoveFromCart $procedure;

    protected function onSetUp(): void
    {
        // 直接使用服务容器获取真实的服务实例，就像 AddToCartTest 一样
        $this->procedure = self::getService(RemoveFromCart::class);
    }

    public function testExecuteWithEmptyCartItemIdShouldThrowException(): void
    {
        $this->procedure->cartItemId = '';

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithWhitespaceCartItemIdShouldThrowException(): void
    {
        $this->procedure->cartItemId = '   ';

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithValidCartItemIdShouldNotThrowException(): void
    {
        $this->procedure->cartItemId = 'cart-item-123';

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithLongCartItemIdShouldNotThrowException(): void
    {
        $longId = str_repeat('a', 255);
        $this->procedure->cartItemId = $longId;

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithSpecialCharactersShouldNotThrowException(): void
    {
        $specialId = 'cart-item-123-αβγ-émojì-测试';
        $this->procedure->cartItemId = $specialId;

        // 应该不抛出异常
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithUnauthenticatedUserShouldReturnFailure(): void
    {
        // 确保没有用户登录，像 AddToCartTest 一样
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $this->procedure->cartItemId = 'cart-item-123';

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }

    public function testGetMockResultShouldReturnExpectedStructure(): void
    {
        $result = RemoveFromCart::getMockResult();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }
}
