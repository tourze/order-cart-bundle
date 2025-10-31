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
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Procedure\SetCartItemChecked;
use Tourze\OrderCartBundle\Repository\CartItemRepository;

/**
 * @internal
 */
#[CoversClass(SetCartItemChecked::class)]
#[RunTestsInSeparateProcesses]
final class SetCartItemCheckedTest extends AbstractProcedureTestCase
{
    private CartItemRepository&MockObject $cartItemRepository;

    private LoggerInterface&MockObject $procedureLogger;

    private SetCartItemChecked $procedure;

    private UserInterface $user;

    public function testExecute(): void
    {
        // 基本的执行测试 - 验证方法可调用

        $itemIds = ['test-item-1'];
        $checked = true;

        $this->procedure->itemIds = $itemIds;
        $this->procedure->checked = $checked;

        $cartItem = $this->createCartItem('test-item-1');

        $this->cartItemRepository
            ->method('findByUserAndIds')
            ->willReturn([$cartItem])
        ;
        $this->cartItemRepository
            ->method('batchUpdateCheckedStatus')
            ->willReturn(1)
        ;
        $this->cartItemRepository
            ->method('countByUser')
            ->willReturn(5)
        ;
        $this->cartItemRepository
            ->method('getTotalQuantityByUser')
            ->willReturn(15)
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
        $this->procedure = self::getService(SetCartItemChecked::class);
    }

    public function testSetCheckedOperationWithTrueStatusShouldSucceed(): void
    {
        $itemIds = ['item1', 'item2', 'item3'];
        $checked = true;

        $this->procedure->itemIds = $itemIds;
        $this->procedure->checked = $checked;

        $cartItems = [
            $this->createCartItem('item1'),
            $this->createCartItem('item2'),
            $this->createCartItem('item3'),
        ];

        $this->cartItemRepository
            ->expects($this->once())
            ->method('findByUserAndIds')
            ->with($this->user, $itemIds)
            ->willReturn($cartItems)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('batchUpdateCheckedStatus')
            ->with($this->user, $itemIds, $checked)
            ->willReturn(3)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(5)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('getTotalQuantityByUser')
            ->with($this->user)
            ->willReturn(15)
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['affectedCount']);
        $this->assertEquals(5, $result['totalCartItems']);
        $this->assertEquals(15, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功勾选3个购物车项目', $result['message']);
        self::assertIsArray($result['errors']);
        $this->assertEmpty($result['errors']);
    }

    public function testSetCheckedOperationWithFalseStatusShouldSucceed(): void
    {
        $itemIds = ['item1', 'item2'];
        $checked = false;

        $this->procedure->itemIds = $itemIds;
        $this->procedure->checked = $checked;

        $cartItems = [
            $this->createCartItem('item1'),
            $this->createCartItem('item2'),
        ];

        $this->cartItemRepository
            ->expects($this->once())
            ->method('findByUserAndIds')
            ->with($this->user, $itemIds)
            ->willReturn($cartItems)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('batchUpdateCheckedStatus')
            ->with($this->user, $itemIds, $checked)
            ->willReturn(2)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('countByUser')
            ->with($this->user)
            ->willReturn(4)
        ;

        $this->cartItemRepository
            ->expects($this->once())
            ->method('getTotalQuantityByUser')
            ->with($this->user)
            ->willReturn(12)
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['affectedCount']);
        $this->assertEquals(4, $result['totalCartItems']);
        $this->assertEquals(12, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功取消勾选2个购物车项目', $result['message']);
    }

    public function testEmptyItemIdsShouldReturnFailure(): void
    {
        $this->procedure->itemIds = [];
        $this->procedure->checked = true;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        $this->assertEquals(0, $result['totalCartItems']);
        $this->assertEquals(0, $result['totalQuantity']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('项目ID列表不能为空', $result['message']);
    }

    public function testTooManyItemIdsShouldReturnFailure(): void
    {
        $this->procedure->itemIds = array_fill(0, 201, 'item_id');
        $this->procedure->checked = true;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('一次最多操作200个项目', $result['message']);
    }

    public function testInvalidItemIdsShouldReturnFailure(): void
    {
        $this->procedure->itemIds = ['item1', '', 'item3'];
        $this->procedure->checked = true;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('项目ID必须为非空字符串', $result['message']);
    }

    public function testDuplicateItemIdsShouldReturnFailure(): void
    {
        $this->procedure->itemIds = ['item1', 'item2', 'item1'];
        $this->procedure->checked = true;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('项目ID列表包含重复项', $result['message']);
    }

    public function testMissingCartItemsShouldReturnFailure(): void
    {
        $itemIds = ['item1', 'item2', 'item3'];

        $this->procedure->itemIds = $itemIds;
        $this->procedure->checked = true;

        $cartItems = [
            $this->createCartItem('item1'),
            $this->createCartItem('item2'),
        ];

        $this->cartItemRepository
            ->expects($this->once())
            ->method('findByUserAndIds')
            ->with($this->user, $itemIds)
            ->willReturn($cartItems)
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('部分购物车项目不存在或不属于当前用户', $result['message']);
        $this->assertStringContainsString('item3', $result['message']);
    }

    public function testUnauthenticatedUserShouldReturnFailure(): void
    {
        // 清除认证状态
        $tokenStorage = self::getContainer()->get(TokenStorageInterface::class);
        self::assertInstanceOf(TokenStorageInterface::class, $tokenStorage);
        $tokenStorage->setToken(null);

        $procedure = self::getService(SetCartItemChecked::class);

        $procedure->itemIds = ['item1'];
        $procedure->checked = true;

        $result = $procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('assert($user instanceof UserInterface)', $result['message']);
    }

    public function testLoggingShouldRecordOperationDetails(): void
    {
        $this->procedure->itemIds = ['item1'];
        $this->procedure->checked = true;

        $cartItems = [$this->createCartItem('item1')];

        $this->cartItemRepository->method('findByUserAndIds')->willReturn($cartItems);
        $this->cartItemRepository->method('batchUpdateCheckedStatus')->willReturn(1);
        $this->cartItemRepository->method('countByUser')->willReturn(3);
        $this->cartItemRepository->method('getTotalQuantityByUser')->willReturn(10);

        $this->procedureLogger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(
                self::logicalOr(
                    $this->equalTo('设置购物车项目选中状态'),
                    $this->equalTo('设置购物车项目选中状态完成')
                ),
                self::isArray()
            )
        ;

        $this->procedure->execute();
    }

    public function testRepositoryExceptionShouldTriggerRollbackAndErrorLogging(): void
    {
        $this->procedure->itemIds = ['item1'];
        $this->procedure->checked = true;

        $this->cartItemRepository
            ->method('findByUserAndIds')
            ->willThrowException(new \RuntimeException('Database error'))
        ;

        $this->procedureLogger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('设置购物车项目选中状态失败'),
                self::isArray()
            )
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('Database error', $result['message']);
    }

    public function testBatchUpdateCheckedStatusExceptionShouldTriggerRollback(): void
    {
        $this->procedure->itemIds = ['item1'];
        $this->procedure->checked = true;

        $cartItems = [$this->createCartItem('item1')];

        $this->cartItemRepository->method('findByUserAndIds')->willReturn($cartItems);
        $this->cartItemRepository
            ->method('batchUpdateCheckedStatus')
            ->willThrowException(new \RuntimeException('Update failed'))
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertStringContainsString('Update failed', $result['message']);
    }

    public function testNumericStringItemIdShouldSucceed(): void
    {
        $this->procedure->itemIds = ['item1', '123', 'item3']; // 数字字符串是有效的
        $this->procedure->checked = true;

        $cartItems = [
            $this->createCartItem('item1'),
            $this->createCartItem('123'),
            $this->createCartItem('item3'),
        ];

        $this->cartItemRepository->method('findByUserAndIds')->willReturn($cartItems);
        $this->cartItemRepository->method('batchUpdateCheckedStatus')->willReturn(3);
        $this->cartItemRepository->method('countByUser')->willReturn(3);
        $this->cartItemRepository->method('getTotalQuantityByUser')->willReturn(9);

        $result = $this->procedure->execute();

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['affectedCount']);
    }

    public function testSingleItemOperationShouldSucceed(): void
    {
        $itemIds = ['single-item'];
        $checked = true;

        $this->procedure->itemIds = $itemIds;
        $this->procedure->checked = $checked;

        $cartItems = [$this->createCartItem('single-item')];

        $this->cartItemRepository->method('findByUserAndIds')->willReturn($cartItems);
        $this->cartItemRepository->method('batchUpdateCheckedStatus')->willReturn(1);
        $this->cartItemRepository->method('countByUser')->willReturn(1);
        $this->cartItemRepository->method('getTotalQuantityByUser')->willReturn(3);

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['affectedCount']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('成功勾选1个购物车项目', $result['message']);
    }

    private function createCartItem(string $id): CartItem
    {
        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getId')->willReturn($id);

        return $cartItem;
    }
}
