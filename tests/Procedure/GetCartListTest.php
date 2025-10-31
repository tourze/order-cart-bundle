<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;
use Tourze\OrderCartBundle\Procedure\GetCartList;

/**
 * @internal
 */
#[CoversClass(GetCartList::class)]
#[RunTestsInSeparateProcesses]
final class GetCartListTest extends AbstractProcedureTestCase
{
    private CartDataProviderInterface&MockObject $cartDataProvider;

    private LoggerInterface&MockObject $procedureLogger;

    private GetCartList $procedure;

    private UserInterface $user;

    protected function onSetUp(): void
    {
        $this->setUpMocks();
    }

    private function setUpMocks(): void
    {
        $this->cartDataProvider = $this->createMock(CartDataProviderInterface::class);
        $this->procedureLogger = $this->createMock(LoggerInterface::class);

        // 使用基类提供的用户创建方法，避免Mock Security服务
        $this->user = $this->createNormalUser('testuser@example.com');

        // 将 mock 对象设置到容器中
        self::getContainer()->set(CartDataProviderInterface::class, $this->cartDataProvider);
        self::getContainer()->set('monolog.logger.order_cart', $this->procedureLogger);

        // 设置认证用户
        $this->setAuthenticatedUser($this->user);

        // 从容器中获取服务而不是直接实例化
        $this->procedure = self::getService(GetCartList::class);
    }

    public function testExecuteWithAllItemsShouldReturnFullCartList(): void
    {
        $cartItems = [
            $this->createCartItemDTO('item1', 1, true, 99.99),
            $this->createCartItemDTO('item2', 2, false, 59.99),
        ];

        $cartSummary = $this->createCartSummaryDTO(2, 1, 219.97, 99.99);

        $this->procedure->selectedOnly = false;

        $this->cartDataProvider
            ->expects($this->once())
            ->method('getCartItems')
            ->with($this->user)
            ->willReturn($cartItems)
        ;

        $this->cartDataProvider
            ->expects($this->never())
            ->method('getSelectedItems')
        ;

        $this->cartDataProvider
            ->expects($this->once())
            ->method('getCartSummary')
            ->with($this->user)
            ->willReturn($cartSummary)
        ;

        $this->procedureLogger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(
                self::logicalOr(
                    $this->equalTo('获取购物车列表'),
                    $this->equalTo('获取购物车列表完成')
                ),
                self::isArray()
            )
        ;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('summary', $result);
        self::assertIsArray($result['items']);
        $this->assertCount(2, $result['items']);

        self::assertIsArray($result['items'][0]);
        $this->assertEquals('item1', $result['items'][0]['id']);
        $this->assertEquals(1, $result['items'][0]['quantity']);
        $this->assertTrue($result['items'][0]['selected']);

        self::assertIsArray($result['items'][1]);
        $this->assertEquals('item2', $result['items'][1]['id']);
        $this->assertEquals(2, $result['items'][1]['quantity']);
        $this->assertFalse($result['items'][1]['selected']);

        self::assertIsArray($result['summary']);
        $this->assertEquals(2, $result['summary']['totalItems']);
        $this->assertEquals(1, $result['summary']['selectedItems']);
    }

    public function testExecuteWithSelectedOnlyShouldReturnSelectedItems(): void
    {
        $selectedItems = [
            $this->createCartItemDTO('item1', 1, true, 99.99),
        ];

        $cartSummary = $this->createCartSummaryDTO(2, 1, 219.97, 99.99);

        $this->procedure->selectedOnly = true;

        $this->cartDataProvider
            ->expects($this->never())
            ->method('getCartItems')
        ;

        $this->cartDataProvider
            ->expects($this->once())
            ->method('getSelectedItems')
            ->with($this->user)
            ->willReturn($selectedItems)
        ;

        $this->cartDataProvider
            ->expects($this->once())
            ->method('getCartSummary')
            ->with($this->user)
            ->willReturn($cartSummary)
        ;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        self::assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
        self::assertIsArray($result['items'][0]);
        $this->assertEquals('item1', $result['items'][0]['id']);
        $this->assertTrue($result['items'][0]['selected']);
    }

    public function testExecuteWithEmptyCartShouldReturnEmptyResult(): void
    {
        $this->procedure->selectedOnly = false;

        $this->cartDataProvider
            ->method('getCartItems')
            ->willReturn([])
        ;

        $this->cartDataProvider
            ->method('getCartSummary')
            ->willReturn($this->createCartSummaryDTO(0, 0, 0.00, 0.00))
        ;

        $this->procedureLogger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        self::assertIsArray($result['items']);
        $this->assertEmpty($result['items']);
        self::assertIsArray($result['summary']);
        $this->assertEquals(0, $result['summary']['totalItems']);
        $this->assertEquals(0, $result['summary']['selectedItems']);
    }

    public function testExecuteWithEmptySelectedItemsShouldReturnEmptyResult(): void
    {
        $this->procedure->selectedOnly = true;

        $this->cartDataProvider
            ->method('getSelectedItems')
            ->willReturn([])
        ;

        $this->cartDataProvider
            ->method('getCartSummary')
            ->willReturn($this->createCartSummaryDTO(5, 0, 299.95, 0.00))
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result['items']);
        $this->assertEmpty($result['items']);
        self::assertIsArray($result['summary']);
        $this->assertEquals(5, $result['summary']['totalItems']);
        $this->assertEquals(0, $result['summary']['selectedItems']);
    }

    public function testExecuteWithDataProviderExceptionShouldPropagateException(): void
    {
        $this->procedure->selectedOnly = false;

        $this->cartDataProvider
            ->method('getCartItems')
            ->willThrowException(new \RuntimeException('Data provider error'))
        ;

        $this->procedureLogger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('获取购物车列表失败'),
                self::callback(function (array $context) {
                    return 'testuser@example.com' === $context['user_id']
                        && 'Data provider error' === $context['error'];
                })
            )
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Data provider error');

        $this->procedure->execute();
    }

    public function testExecuteWithSummaryProviderExceptionShouldPropagateException(): void
    {
        $cartItems = [
            $this->createCartItemDTO('item1', 1, true, 99.99),
        ];

        $this->procedure->selectedOnly = false;

        $this->cartDataProvider
            ->method('getCartItems')
            ->willReturn($cartItems)
        ;

        $this->cartDataProvider
            ->method('getCartSummary')
            ->willThrowException(new \RuntimeException('Summary calculation failed'))
        ;

        $this->procedureLogger
            ->expects($this->once())
            ->method('error')
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Summary calculation failed');

        $this->procedure->execute();
    }

    public function testExecuteShouldLogCorrectOperationDetails(): void
    {
        $cartItems = [
            $this->createCartItemDTO('item1', 3, true, 149.97),
        ];

        $cartSummary = $this->createCartSummaryDTO(1, 1, 149.97, 149.97);

        $this->procedure->selectedOnly = true;

        $this->cartDataProvider
            ->method('getSelectedItems')
            ->willReturn($cartItems)
        ;

        $this->cartDataProvider
            ->method('getCartSummary')
            ->willReturn($cartSummary)
        ;

        // Verify logging calls
        $this->procedureLogger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(
                self::logicalOr(
                    $this->equalTo('获取购物车列表'),
                    $this->equalTo('获取购物车列表完成')
                ),
                self::isArray()
            )
        ;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        self::assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
    }

    public function testExecuteWithSelectedOnlyFalseShouldLogCorrectFlag(): void
    {
        $this->procedure->selectedOnly = false;

        $this->cartDataProvider
            ->method('getCartItems')
            ->willReturn([])
        ;

        $this->cartDataProvider
            ->method('getCartSummary')
            ->willReturn($this->createCartSummaryDTO(0, 0, 0.00, 0.00))
        ;

        $this->procedureLogger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(
                self::logicalOr(
                    $this->equalTo('获取购物车列表'),
                    $this->equalTo('获取购物车列表完成')
                ),
                self::isArray()
            )
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result['items']);
        $this->assertCount(0, $result['items']);
    }

    public function testExecuteWithMultipleSelectedItemsShouldReturnCorrectData(): void
    {
        $selectedItems = [
            $this->createCartItemDTO('item1', 2, true, 199.98),
            $this->createCartItemDTO('item3', 1, true, 79.99),
        ];

        $cartSummary = $this->createCartSummaryDTO(5, 2, 599.95, 279.97);

        $this->procedure->selectedOnly = true;

        $this->cartDataProvider
            ->method('getSelectedItems')
            ->willReturn($selectedItems)
        ;

        $this->cartDataProvider
            ->method('getCartSummary')
            ->willReturn($cartSummary)
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result['items']);
        $this->assertCount(2, $result['items']);

        self::assertIsArray($result['items'][0]);
        $this->assertEquals('item1', $result['items'][0]['id']);
        $this->assertEquals(2, $result['items'][0]['quantity']);

        self::assertIsArray($result['items'][1]);
        $this->assertEquals('item3', $result['items'][1]['id']);
        $this->assertEquals(1, $result['items'][1]['quantity']);

        self::assertIsArray($result['summary']);
        $this->assertEquals(5, $result['summary']['totalItems']);
        $this->assertEquals(2, $result['summary']['selectedItems']);
        $this->assertEquals('599.95', $result['summary']['totalAmount']);
        $this->assertEquals('279.97', $result['summary']['selectedAmount']);
    }

    private function createCartItemDTO(string $id, int $quantity, bool $selected, float $price): CartItemDTO
    {
        $productDto = new ProductDTO(
            substr($id, -1),
            'Product ' . $id,
            sprintf('%.2f', $price / $quantity),
            100,
            true
        );

        return new CartItemDTO(
            $id,
            $productDto,
            $quantity,
            $selected,
            [],
            new \DateTimeImmutable('2023-01-01T12:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T12:00:00+00:00')
        );
    }

    private function createCartSummaryDTO(int $totalItems, int $selectedItems, float $totalAmount, float $selectedAmount): CartSummaryDTO
    {
        return new CartSummaryDTO(
            $totalItems,
            $selectedItems,
            sprintf('%.2f', $totalAmount),
            sprintf('%.2f', $selectedAmount)
        );
    }
}
