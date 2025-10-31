<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Interface;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\CartException;
use Tourze\OrderCartBundle\Exception\CartItemNotFoundException;
use Tourze\OrderCartBundle\Exception\CartLimitExceededException;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\ProductCoreBundle\Entity\Sku;

interface CartManagerInterface
{
    /**
     * @param array<string, mixed> $metadata
     *
     * @throws InvalidSkuException
     * @throws InvalidQuantityException
     * @throws CartLimitExceededException
     */
    public function addItem(UserInterface $user, Sku $sku, int $quantity, array $metadata = []): CartItem;

    /**
     * @throws CartItemNotFoundException
     * @throws InvalidQuantityException
     * @throws CartException
     */
    public function updateQuantity(UserInterface $user, string $cartItemId, int $quantity): CartItem;

    /**
     * @throws CartItemNotFoundException
     * @throws CartException
     */
    public function removeItem(UserInterface $user, string $cartItemId): void;

    /**
     * @throws CartException
     */
    public function clearCart(UserInterface $user): int;

    /**
     * @throws CartItemNotFoundException
     * @throws CartException
     */
    public function updateSelection(UserInterface $user, string $cartItemId, bool $selected): CartItem;

    /**
     * @param array<string> $cartItemIds
     *
     * @return array<string, CartItem>
     *
     * @throws CartException
     */
    public function batchUpdateSelection(UserInterface $user, array $cartItemIds, bool $selected): array;

    /**
     * 获取用户购物车中的项目数量
     */
    public function getCartItemCount(UserInterface $user): int;

    /**
     * 获取用户购物车中所有商品的总数量
     */
    public function getCartTotalQuantity(UserInterface $user): int;
}
