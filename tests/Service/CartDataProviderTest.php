<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\CartException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\CartDataProvider;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\StockManageBundle\Model\StockSummary;
use Tourze\StockManageBundle\Service\StockServiceInterface;

/**
 * @internal
 */
#[CoversClass(CartDataProvider::class)]
#[RunTestsInSeparateProcesses]
final class CartDataProviderTest extends AbstractIntegrationTestCase
{
    private CartDataProvider $dataProvider;

    private CartItemRepository&MockObject $repository;

    private StockServiceInterface&MockObject $stockService;

    protected function onSetUp(): void
    {
        $this->repository = $this->createMock(CartItemRepository::class);
        $this->stockService = $this->createMock(StockServiceInterface::class);

        // Inject mocked dependencies into container before getting service
        self::getContainer()->set(CartItemRepository::class, $this->repository);
        self::getContainer()->set(StockServiceInterface::class, $this->stockService);

        $this->dataProvider = self::getService(CartDataProvider::class);
    }

    public function testGetCartSummaryWithItems(): void
    {
        $user = $this->createMock(UserInterface::class);

        // Create SKUs with null market price (will result in no prices)
        $sku1 = $this->createMock(Sku::class);
        $sku1->method('getId')->willReturn('1');
        $sku1->method('getMarketPrice')->willReturn(null);

        $sku2 = $this->createMock(Sku::class);
        $sku2->method('getId')->willReturn('2');
        $sku2->method('getMarketPrice')->willReturn(null);

        $cartItem1 = new CartItem();
        $cartItem1->setId('1');
        $cartItem1->setSku($sku1);
        $cartItem1->setQuantity(2);
        $cartItem1->setSelected(true);

        $cartItem2 = new CartItem();
        $cartItem2->setId('2');
        $cartItem2->setSku($sku2);
        $cartItem2->setQuantity(1);
        $cartItem2->setSelected(false);

        $this->repository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn([$cartItem1, $cartItem2])
        ;

        $summary = $this->dataProvider->getCartSummary($user);

        $this->assertInstanceOf(CartSummaryDTO::class, $summary);
        $this->assertEquals(2, $summary->getTotalItems());
        // Since there are no prices, selected items and amounts will be 0
        $this->assertEquals(0, $summary->getSelectedItems());
        $this->assertEquals(0, $summary->getSelectedAmount());
        $this->assertEquals(0, $summary->getTotalAmount());
    }

    public function testGetCartSummaryEmpty(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->repository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn([])
        ;

        $summary = $this->dataProvider->getCartSummary($user);

        $this->assertInstanceOf(CartSummaryDTO::class, $summary);
        $this->assertEquals(0, $summary->getTotalItems());
        $this->assertEquals(0, $summary->getSelectedItems());
        $this->assertEquals(0, $summary->getSelectedAmount());
        $this->assertEquals(0, $summary->getTotalAmount());
    }

    public function testGetCartItemsEmpty(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->repository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn([])
        ;

        $items = $this->dataProvider->getCartItems($user);

        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function testGetSelectedItems(): void
    {
        $user = $this->createMock(UserInterface::class);

        $sku1 = $this->createMock(Sku::class);
        $sku1->method('getId')->willReturn('1');
        $sku1->method('getFullName')->willReturn('Product 1');
        $sku1->method('getSpu')->willReturn(null);
        $sku1->method('getMarketPrice')->willReturn(null);

        $cartItem1 = new CartItem();
        $cartItem1->setId('1');
        $cartItem1->setSku($sku1);
        $cartItem1->setQuantity(2);
        $cartItem1->setSelected(true);

        $this->repository->expects($this->once())
            ->method('findSelectedByUser')
            ->with($user)
            ->willReturn([$cartItem1])
        ;

        $stockSummary = $this->createMock(StockSummary::class);
        $stockSummary->method('getAvailableQuantity')->willReturn(100);

        $this->stockService->expects($this->never())
            ->method('getAvailableStock')
        ;

        $items = $this->dataProvider->getSelectedItems($user);

        // Since there's no price, the product won't be included
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function testGetItemCount(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->repository->expects($this->once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(5)
        ;

        $count = $this->dataProvider->getItemCount($user);

        $this->assertEquals(5, $count);
    }

    public function testGetItemByIdFound(): void
    {
        $user = $this->createMock(UserInterface::class);

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('1');
        $sku->method('getFullName')->willReturn('Product 1');
        $sku->method('getSpu')->willReturn(null);
        $sku->method('getMarketPrice')->willReturn(null);

        $cartItem = new CartItem();
        $cartItem->setId('1');
        $cartItem->setSku($sku);
        $cartItem->setQuantity(2);

        $this->repository->expects($this->once())
            ->method('findByUserAndId')
            ->with($user, '1')
            ->willReturn($cartItem)
        ;

        $stockSummary = $this->createMock(StockSummary::class);
        $stockSummary->method('getAvailableQuantity')->willReturn(100);

        $this->stockService->expects($this->never())
            ->method('getAvailableStock')
        ;

        // Since there's no price, an exception will be thrown
        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Product not found for SKU 1');

        $this->dataProvider->getItemById($user, '1');
    }

    public function testGetItemByIdNotFound(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->repository->expects($this->once())
            ->method('findByUserAndId')
            ->with($user, '999')
            ->willReturn(null)
        ;

        $item = $this->dataProvider->getItemById($user, '999');

        $this->assertNull($item);
    }

    public function testGetSelectedCartEntities(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sku1 = $this->createMock(Sku::class);
        $sku1->method('getId')->willReturn('1');

        $cartItem1 = $this->createMock(CartItem::class);
        $cartItem1->method('getSku')->willReturn($sku1);
        $cartItem1->method('getQuantity')->willReturn(2);
        $cartItem1->method('isSelected')->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findSelectedByUser')
            ->with($user)
            ->willReturn([$cartItem1])
        ;

        $entities = $this->dataProvider->getSelectedCartEntities($user);

        $this->assertCount(1, $entities);
        $this->assertInstanceOf(CartItem::class, $entities[0]);
        $this->assertTrue($entities[0]->isSelected());
    }

    public function testGetSelectedCartEntitiesEmpty(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->repository->expects($this->once())
            ->method('findSelectedByUser')
            ->with($user)
            ->willReturn([])
        ;

        $entities = $this->dataProvider->getSelectedCartEntities($user);

        $this->assertIsArray($entities);
        $this->assertEmpty($entities);
    }
}
