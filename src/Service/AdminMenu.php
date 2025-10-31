<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\OrderCartBundle\Entity\CartItem;

/**
 * 购物车管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('订单管理')) {
            $item->addChild('订单管理');
        }

        $orderMenu = $item->getChild('订单管理');
        if (null === $orderMenu) {
            return;
        }

        // 购物车管理菜单
        $orderMenu->addChild('购物车管理')
            ->setUri($this->linkGenerator->getCurdListPage(CartItem::class))
            ->setAttribute('icon', 'fas fa-shopping-cart')
        ;

        // 购物车加购记录菜单
        $orderMenu->addChild('购物车加购记录')
            ->setUri($this->linkGenerator->getCurdListPage(CartAddLog::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }
}
