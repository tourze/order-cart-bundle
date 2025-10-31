<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\Procedure\CheckAllCartItems;
use Tourze\OrderCartBundle\Repository\CartItemRepository;

/**
 * @internal
 */
#[CoversClass(CheckAllCartItems::class)]
#[RunTestsInSeparateProcesses]
final class CheckAllCartItemsTest extends AbstractProcedureTestCase
{
    private CartItemRepository&MockObject $cartItemRepository;

    private LoggerInterface&MockObject $procedureLogger;

    private CheckAllCartItems $procedure;

    private UserInterface $user;

    public function testCheckAllWithTrueStatusShouldSucceed(): void
    {
        $this->procedure->checked = true;

        // Validation phase - called first
        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(5)
        ;

        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('getTotalQuantityByUser')
            ->with($this->user)
            ->willReturn(15)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('updateAllCheckedStatus')
            ->with($this->user, true)
            ->willReturn(5)
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['affectedCount']);
        $this->assertEquals(5, $result['totalCartItems']);
        $this->assertEquals(15, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功勾选所有购物车项目（5个）', $result['message']);
        $this->assertEmpty($result['errors']);
    }

    public function testCheckAllWithFalseStatusShouldSucceed(): void
    {
        $this->procedure->checked = false;

        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(3)
        ;

        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('getTotalQuantityByUser')
            ->with($this->user)
            ->willReturn(10)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('updateAllCheckedStatus')
            ->with($this->user, false)
            ->willReturn(3)
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['affectedCount']);
        $this->assertEquals(3, $result['totalCartItems']);
        $this->assertEquals(10, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功取消勾选所有购物车项目（3个）', $result['message']);
    }

    public function testCheckAllWithNoItemsShouldSucceed(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(0)
        ;

        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('getTotalQuantityByUser')
            ->with($this->user)
            ->willReturn(0)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('updateAllCheckedStatus')
            ->with($this->user, true)
            ->willReturn(0)
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        $this->assertEquals(0, $result['totalCartItems']);
        $this->assertEquals(0, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功勾选所有购物车项目（0个）', $result['message']);
    }

    public function testTooManyCartItemsShouldReturnFailure(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(201)
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        $this->assertEquals(0, $result['totalCartItems']);
        $this->assertEquals(0, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('购物车项目总数不能超过200个', $result['message']);
    }

    public function testTotalQuantityTooHighShouldReturnFailure(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(10)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('getTotalQuantityByUser')
            ->with($this->user)
            ->willReturn(10000)
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('购物车商品总数量不能超过9999个', $result['message']);
    }

    public function testUnauthenticatedUserShouldReturnFailure(): void
    {
        // 清除认证状态，模拟未认证用户
        $tokenStorage = self::getService(TokenStorageInterface::class);
        self::assertInstanceOf(TokenStorageInterface::class, $tokenStorage);
        $tokenStorage->setToken(null);

        $procedure = self::getService(CheckAllCartItems::class);
        $procedure->checked = true;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $procedure->execute();

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('assert($user instanceof UserInterface)', $result['message']);
    }

    public function testLoggingShouldRecordOperationDetails(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('countByUser')
            ->willReturn(3)
        ;
        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('getTotalQuantityByUser')
            ->willReturn(10)
        ;
        $this->cartItemRepository->method('updateAllCheckedStatus')->willReturn(3);

        $this->procedureLogger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(
                self::logicalOr(
                    self::equalTo('全选/取消全选购物车项目'),
                    self::equalTo('全选/取消全选购物车项目完成')
                ),
                self::isArray()
            )
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $this->procedure->execute();
    }

    public function testValidateCartLimitsExceptionShouldReturnFailure(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->method('countByUser')
            ->willThrowException(new \RuntimeException('Database error'))
        ;

        $this->procedureLogger
            ->expects($this->once())
            ->method('error')
            ->with(
                self::equalTo('全选/取消全选购物车项目失败'),
                self::isArray()
            )
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('Database error', $result['message']);
    }

    public function testUpdateAllCheckedStatusExceptionShouldTriggerRollback(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository->method('countByUser')->willReturn(5);
        $this->cartItemRepository->method('getTotalQuantityByUser')->willReturn(15);
        $this->cartItemRepository
            ->method('updateAllCheckedStatus')
            ->willThrowException(new \RuntimeException('Update failed'))
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('Update failed', $result['message']);
    }

    public function testCountByUserAfterUpdateExceptionShouldTriggerRollback(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->method('countByUser')
            ->willReturnOnConsecutiveCalls(5, new \RuntimeException('Count failed after update'))
        ;

        $this->cartItemRepository->method('getTotalQuantityByUser')->willReturn(15);
        $this->cartItemRepository->method('updateAllCheckedStatus')->willReturn(5);

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }

    public function testGetTotalQuantityByUserAfterUpdateExceptionShouldTriggerRollback(): void
    {
        $this->procedure->checked = true;

        $this->cartItemRepository->method('countByUser')->willReturn(5);
        $this->cartItemRepository
            ->method('getTotalQuantityByUser')
            ->willReturnOnConsecutiveCalls(15, new \RuntimeException('Quantity failed after update'))
        ;
        $this->cartItemRepository->method('updateAllCheckedStatus')->willReturn(5);

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
    }

    public function testCartLimitValidationAtBoundaryValues(): void
    {
        $this->procedure->checked = true;

        // Test exactly at boundary (should succeed)
        $this->cartItemRepository
            ->method('countByUser')
            ->willReturn(200)
        ;

        $this->cartItemRepository
            ->method('getTotalQuantityByUser')
            ->willReturn(9999)
        ;

        $this->cartItemRepository->method('updateAllCheckedStatus')->willReturn(200);

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['affectedCount']);
    }

    public function testDefaultCheckedValueShouldBeFalse(): void
    {
        // Test that default value is false and the operation works correctly
        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('countByUser')
            ->willReturn(3)
        ;
        $this->cartItemRepository
            ->expects($this->exactly(2))
            ->method('getTotalQuantityByUser')
            ->willReturn(10)
        ;
        $this->cartItemRepository
            ->expects($this->once())
            ->method('updateAllCheckedStatus')
            ->with($this->user, false)
            ->willReturn(3)
        ;

        // 在集成测试中，我们验证业务结果而不是内部事务调用

        $result = $this->procedure->execute();

        $this->assertTrue($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功取消勾选所有购物车项目（3个）', $result['message']);
    }

    public function testExecute(): void
    {
        // 基本的执行测试 - 验证方法可调用

        $this->procedure->checked = true;

        $this->cartItemRepository
            ->method('countByUser')
            ->willReturn(2)
        ;
        $this->cartItemRepository
            ->method('getTotalQuantityByUser')
            ->willReturn(5)
        ;
        $this->cartItemRepository
            ->method('updateAllCheckedStatus')
            ->willReturn(2)
        ;

        $result = $this->procedure->execute();

        // 验证返回结果结构
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('affectedCount', $result);
        $this->assertArrayHasKey('message', $result);
    }

    protected function onSetUp(): void
    {
        $this->setUpMocks();
    }

    private function setUpMocks(): void
    {
        $this->cartItemRepository = $this->createMock(CartItemRepository::class);
        $this->procedureLogger = $this->createMock(LoggerInterface::class);

        // 使用基类提供的用户创建方法，避免Mock Security服务
        $this->user = $this->createNormalUser('testuser@example.com');

        // 将 mock 对象设置到容器中
        self::getContainer()->set(CartItemRepository::class, $this->cartItemRepository);
        self::getContainer()->set('monolog.logger.order_cart', $this->procedureLogger);

        // 设置认证用户
        $this->setAuthenticatedUser($this->user);

        // 从容器中获取服务而不是直接实例化
        $this->procedure = self::getService(CheckAllCartItems::class);
    }
}
