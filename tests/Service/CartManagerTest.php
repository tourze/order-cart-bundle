<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\LockServiceBundle\Service\LockService;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\CartLimitExceededException;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\CartAddLogService;
use Tourze\OrderCartBundle\Service\CartManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductServiceContracts\SkuLoaderInterface;
use Tourze\StockManageBundle\Model\StockSummary;
use Tourze\StockManageBundle\Service\StockServiceInterface;

/**
 * @internal
 */
#[CoversClass(CartManager::class)]
#[RunTestsInSeparateProcesses]
final class CartManagerTest extends AbstractIntegrationTestCase
{
    private CartManager $cartManager;

    private MockObject $repository;

    private MockObject $skuLoader;

    private MockObject $stockService;

    private MockObject $eventDispatcher;

    private MockObject $lockService;

    private MockObject $cartAddLogService;

    protected function onSetUp(): void
    {
        $this->repository = $this->createMock(CartItemRepository::class);
        $this->skuLoader = $this->createMock(SkuLoaderInterface::class);
        $this->stockService = $this->createMock(StockServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->lockService = $this->createMock(LockService::class);
        $this->cartAddLogService = $this->createMock(CartAddLogService::class);

        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - 需要使用Mock依赖验证行为
        $this->cartManager = new CartManager(
            $this->repository,
            $this->skuLoader,
            $this->stockService,
            $this->eventDispatcher,
            $this->lockService,
            $this->cartAddLogService
        );
    }

    public function testAddItemSuccess(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('1');

        $this->skuLoader->expects($this->once())
            ->method('loadSkuByIdentifier')
            ->with($sku->getId())
            ->willReturn($sku)
        ;

        $stockSummary = $this->createMock(StockSummary::class);
        $stockSummary->method('getAvailableQuantity')->willReturn(100);

        $this->stockService->expects($this->once())
            ->method('getAvailableStock')
            ->with($sku)
            ->willReturn($stockSummary)
        ;

        $this->repository->expects($this->once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(5)
        ;

        $this->repository->expects($this->once())
            ->method('findByUserAndSku')
            ->with($user, $sku)
            ->willReturn(null)
        ;

        $cartItem = new CartItem();
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (CartItem $item) use ($cartItem): CartItem {
                $item->setId('1');

                return $cartItem;
            })
        ;

        $result = $this->cartManager->addItem($user, $sku, 2, ['note' => 'test']);

        $this->assertInstanceOf(CartItem::class, $result);
    }

    public function testAddItemInvalidSku(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $this->skuLoader->expects($this->once())
            ->method('loadSkuByIdentifier')
            ->with($sku->getId())
            ->willReturn(null)
        ;

        $this->expectException(InvalidSkuException::class);
        $this->cartManager->addItem($user, $sku, 1);
    }

    public function testAddItemInvalidQuantity(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);

        $this->expectException(InvalidQuantityException::class);
        $this->cartManager->addItem($user, $sku, 0);
    }

    public function testAddItemExceedLimit(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('test-sku-123');

        $this->skuLoader->expects($this->once())
            ->method('loadSkuByIdentifier')
            ->with('test-sku-123')
            ->willReturn($sku)
        ;

        $stockSummary = $this->createMock(StockSummary::class);
        $stockSummary->method('getAvailableQuantity')->willReturn(100);

        $this->stockService->expects($this->once())
            ->method('getAvailableStock')
            ->willReturn($stockSummary)
        ;

        $this->repository->expects($this->once())
            ->method('countByUser')
            ->willReturn(100)
        ;

        $this->expectException(CartLimitExceededException::class);
        $this->cartManager->addItem($user, $sku, 1);
    }

    public function testUpdateQuantitySuccess(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = new CartItem();
        $cartItem->setId('1');
        $cartItem->setQuantity(2);

        $sku = $this->createMock(Sku::class);
        $cartItem->setSku($sku);

        $this->repository->expects($this->once())
            ->method('findByUserAndId')
            ->with($user, '1')
            ->willReturn($cartItem)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($cartItem)
        ;

        $this->lockService->expects($this->once())
            ->method('blockingRun')
            ->willReturnCallback(function ($key, callable $callback) {
                return $callback();
            })
        ;

        $result = $this->cartManager->updateQuantity($user, '1', 5);

        $this->assertEquals(5, $result->getQuantity());
    }

    public function testRemoveItemSuccess(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = new CartItem();
        $cartItem->setId('1');

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('10');
        $cartItem->setSku($sku);

        $this->repository->expects($this->once())
            ->method('findByUserAndId')
            ->with($user, '1')
            ->willReturn($cartItem)
        ;

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($cartItem)
        ;

        $this->cartManager->removeItem($user, '1');
    }

    public function testClearCartSuccess(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItems = [
            new CartItem(),
            new CartItem(),
            new CartItem(),
        ];

        $this->repository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($cartItems)
        ;

        $this->repository->expects($this->exactly(3))
            ->method('remove')
        ;

        $count = $this->cartManager->clearCart($user);

        $this->assertEquals(3, $count);
    }

    public function testUpdateSelectionSuccess(): void
    {
        $user = $this->createMock(UserInterface::class);
        $cartItem = new CartItem();
        $cartItem->setId('1');
        $cartItem->setSelected(false);

        $this->repository->expects($this->once())
            ->method('findByUserAndId')
            ->with($user, '1')
            ->willReturn($cartItem)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($cartItem)
        ;

        $result = $this->cartManager->updateSelection($user, '1', true);

        $this->assertTrue($result->isSelected());
    }

    public function testBatchUpdateSelectionSuccess(): void
    {
        $user = $this->createMock(UserInterface::class);

        $cartItem1 = new CartItem();
        $cartItem1->setId('1');
        $cartItem1->setSelected(false);

        $cartItem2 = new CartItem();
        $cartItem2->setId('2');
        $cartItem2->setSelected(false);

        $this->repository->expects($this->once())
            ->method('findByUserAndIds')
            ->with($user, ['1', '2'])
            ->willReturn([$cartItem1, $cartItem2])
        ;

        $this->repository->expects($this->exactly(2))
            ->method('save')
        ;

        $results = $this->cartManager->batchUpdateSelection($user, ['1', '2'], true);

        $this->assertCount(2, $results);
        $resultArray = array_values($results);
        $this->assertTrue($resultArray[0]->isSelected());
        $this->assertTrue($resultArray[1]->isSelected());
    }
}
