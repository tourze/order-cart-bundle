<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\OrderCartBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->method('getCurdListPage')
            ->willReturnCallback(function (mixed $entityClass): string {
                self::assertIsString($entityClass);
                if (str_contains($entityClass, 'CartItem')) {
                    return '/admin?crudAction=index&crudControllerFqcn=CartItem';
                }
                if (str_contains($entityClass, 'CartAddLog')) {
                    return '/admin?crudAction=index&crudControllerFqcn=CartAddLog';
                }

                return '/admin?crudAction=index';
            })
        ;

        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
    }

    public function testInvokeAddsCartMenu(): void
    {
        // Create mock menu item
        $mockMenuItem = $this->createMock(ItemInterface::class);
        $orderMenuItem = $this->createMock(ItemInterface::class);

        // Setup expectations for getChild calls (called twice)
        $mockMenuItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('订单管理')
            ->willReturnOnConsecutiveCalls(null, $orderMenuItem)
        ;

        $mockMenuItem->expects($this->once())
            ->method('addChild')
            ->with('订单管理')
            ->willReturn($orderMenuItem)
        ;

        // Setup expectations for adding cart menu (first call)
        $cartMenuItem = $this->createMock(ItemInterface::class);
        $cartMenuItem->expects($this->once())
            ->method('setUri')
            ->with('/admin?crudAction=index&crudControllerFqcn=CartItem')
            ->willReturnSelf()
        ;

        $cartMenuItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-shopping-cart')
            ->willReturnSelf()
        ;

        // Setup expectations for adding cart add log menu (second call)
        $cartAddLogMenuItem = $this->createMock(ItemInterface::class);
        $cartAddLogMenuItem->expects($this->once())
            ->method('setUri')
            ->with('/admin?crudAction=index&crudControllerFqcn=CartAddLog')
            ->willReturnSelf()
        ;

        $cartAddLogMenuItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-history')
            ->willReturnSelf()
        ;

        $orderMenuItem->expects($this->exactly(2))
            ->method('addChild')
            ->willReturnCallback(function (mixed $childName) use ($cartMenuItem, $cartAddLogMenuItem): ItemInterface {
                self::assertIsString($childName);

                return match ($childName) {
                    '购物车管理' => $cartMenuItem,
                    '购物车加购记录' => $cartAddLogMenuItem,
                    default => throw new \InvalidArgumentException('Unexpected child name: ' . $childName),
                };
            })
        ;

        // Create and invoke the service
        $adminMenu = self::getService(AdminMenu::class);
        $adminMenu($mockMenuItem);
    }

    public function testInvokeWithExistingOrderMenu(): void
    {
        // Create mock menu items
        $mockMenuItem = $this->createMock(ItemInterface::class);
        $existingOrderMenuItem = $this->createMock(ItemInterface::class);

        // Setup expectations - order menu already exists (called twice)
        $mockMenuItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('订单管理')
            ->willReturn($existingOrderMenuItem)
        ;

        $mockMenuItem->expects($this->never())
            ->method('addChild')
        ;

        // Setup expectations for adding cart menu to existing order menu (first call)
        $cartMenuItem = $this->createMock(ItemInterface::class);
        $cartMenuItem->expects($this->once())
            ->method('setUri')
            ->with('/admin?crudAction=index&crudControllerFqcn=CartItem')
            ->willReturnSelf()
        ;

        $cartMenuItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-shopping-cart')
            ->willReturnSelf()
        ;

        // Setup expectations for adding cart add log menu to existing order menu (second call)
        $cartAddLogMenuItem = $this->createMock(ItemInterface::class);
        $cartAddLogMenuItem->expects($this->once())
            ->method('setUri')
            ->with('/admin?crudAction=index&crudControllerFqcn=CartAddLog')
            ->willReturnSelf()
        ;

        $cartAddLogMenuItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-history')
            ->willReturnSelf()
        ;

        $existingOrderMenuItem->expects($this->exactly(2))
            ->method('addChild')
            ->willReturnCallback(function (mixed $childName) use ($cartMenuItem, $cartAddLogMenuItem): ItemInterface {
                self::assertIsString($childName);

                return match ($childName) {
                    '购物车管理' => $cartMenuItem,
                    '购物车加购记录' => $cartAddLogMenuItem,
                    default => throw new \InvalidArgumentException('Unexpected child name: ' . $childName),
                };
            })
        ;

        // Create and invoke the service
        $adminMenu = self::getService(AdminMenu::class);
        $adminMenu($mockMenuItem);
    }
}
