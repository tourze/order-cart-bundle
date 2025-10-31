<?php

namespace Tourze\OrderCartBundle\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartTotalResponse;
use Tourze\OrderCartBundle\DTO\DiscountDetail;
use Tourze\OrderCartBundle\Entity\CartItem;

interface PriceCalculationServiceInterface
{
    /**
     * 计算购物车中已选中商品的总价格
     *
     * @param array<CartItem> $cartItems
     * @return CartTotalResponse
     */
    public function calculateCartTotal(UserInterface $user, array $cartItems, ?string $freightId = null): CartTotalResponse;

    /**
     * 计算商品原始总价
     *
     * @param array<CartItem> $cartItems
     */
    public function calculateProductTotal(array $cartItems): string;

    /**
     * 计算促销优惠
     *
     * @param array<CartItem> $cartItems
     * @return array{discountAmount: string, discountDetails: array<DiscountDetail>}
     */
    public function calculatePromotionDiscount(UserInterface $user, array $cartItems): array;

    /**
     * 计算运费
     *
     * @param array<CartItem> $cartItems
     */
    public function calculateShippingFee(UserInterface $user, array $cartItems, ?string $freightId = null): string;

    /**
     * 检查商品价格是否有变动
     *
     * @param array<CartItem> $cartItems
     * @return array<string, array{oldPrice: string, newPrice: string}>
     */
    public function checkPriceChanges(array $cartItems): array;
}
