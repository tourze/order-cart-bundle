<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Interface;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\CartException;

interface CartDataProviderInterface
{
    /**
     * @throws CartException
     */
    public function getCartSummary(UserInterface $user): CartSummaryDTO;

    /**
     * @return array<int, CartItemDTO>
     *
     * @throws CartException
     */
    public function getCartItems(UserInterface $user): array;

    /**
     * @return array<int, CartItemDTO>
     *
     * @throws CartException
     */
    public function getSelectedItems(UserInterface $user): array;

    /**
     * @throws CartException
     */
    public function getItemCount(UserInterface $user): int;

    /**
     * @throws CartException
     */
    public function getItemById(UserInterface $user, string $cartItemId): ?CartItemDTO;

    /**
     * 获取用户选中的购物车原始实体（用于需要实体的业务逻辑，如库存验证）
     *
     * @return CartItem[]
     * @throws CartException
     */
    public function getSelectedCartEntities(UserInterface $user): array;
}
