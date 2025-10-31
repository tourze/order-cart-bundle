<?php

namespace Tourze\OrderCartBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\DTO\CartTotalResponse;
use Tourze\OrderCartBundle\DTO\DiscountDetail;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\ProductCoreBundle\Service\PriceService;

#[AsAlias(id: PriceCalculationServiceInterface::class)]
#[WithMonologChannel(channel: 'order_cart')]
final class PriceCalculationService implements PriceCalculationServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PriceService $priceService,
    ) {
    }

    public function calculateCartTotal(UserInterface $user, array $cartItems, ?string $freightId = null): CartTotalResponse
    {
        if ([] === $cartItems) {
            return CartTotalResponse::success('0.00', '0.00', '0.00', '0.00', '0.00');
        }

        try {
            // 检查价格变动
            $priceChanges = $this->checkPriceChanges($cartItems);
            if ([] !== $priceChanges) {
                $this->logger->warning('购物车商品价格发生变动', [
                    'user_id' => $user->getUserIdentifier(),
                    'price_changes' => $priceChanges,
                ]);
            }

            // 计算商品原始总价
            $originalAmount = $this->calculateProductTotal($cartItems);

            // 计算促销优惠
            $promotionResult = $this->calculatePromotionDiscount($user, $cartItems);
            $discountAmount = $promotionResult['discountAmount'];
            $discountDetails = $promotionResult['discountDetails'];

            // 计算商品实际金额
            $productAmount = $this->subtractAmounts($originalAmount, $discountAmount);

            // 计算运费
            $shippingFee = $this->calculateShippingFee($user, $cartItems, $freightId);

            // 检查是否有免邮优惠
            $hasFreeShipping = array_filter($discountDetails, fn (DiscountDetail $detail) => $detail->isFreeFreight());
            if ([] !== $hasFreeShipping) {
                $shippingFee = '0.00';
            }

            // 计算最终总价
            $totalAmount = $this->addAmounts($productAmount, $shippingFee);

            $this->logger->info('购物车价格计算完成', [
                'user_id' => $user->getUserIdentifier(),
                'original_amount' => $originalAmount,
                'product_amount' => $productAmount,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'discount_count' => count($discountDetails),
            ]);

            return CartTotalResponse::success(
                originalAmount: $originalAmount,
                productAmount: $productAmount,
                discountAmount: $discountAmount,
                shippingFee: $shippingFee,
                totalAmount: $totalAmount,
                discountDetails: $discountDetails,
                message: [] !== $priceChanges ? '部分商品价格已更新' : null
            );
        } catch (\Throwable $e) {
            $this->logger->error('购物车价格计算失败', [
                'user_id' => $user->getUserIdentifier(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return CartTotalResponse::failure('价格计算失败: ' . $e->getMessage());
        }
    }

    public function calculateProductTotal(array $cartItems): string
    {
        $total = 0.0;

        foreach ($cartItems as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            $sku = $cartItem->getSku();

            // 获取商品当前价格
            $marketPrice = $sku->getMarketPrice();
            if (null === $marketPrice) {
                continue;
            }

            $itemTotal = (float) $marketPrice * $cartItem->getQuantity();
            $total += $itemTotal;
        }

        return number_format($total, 2, '.', '');
    }

    public function calculatePromotionDiscount(UserInterface $user, array $cartItems): array
    {
        $discountDetails = [];
        $totalDiscount = 0.0;

        try {
            // 这里集成promotion-engine-bundle的优惠计算逻辑
            // 目前提供基础实现，后续可扩展具体的促销规则

            // 示例：满额减免优惠
            $productTotal = (float) $this->calculateProductTotal($cartItems);

            if ($productTotal >= 500.0) {
                $reductionAmount = 50.0;
                $discountDetails[] = new DiscountDetail(
                    type: 'reduction',
                    name: '满500减50',
                    amount: number_format($reductionAmount, 2, '.', ''),
                    description: '满500元立减50元'
                );
                $totalDiscount += $reductionAmount;
            } elseif ($productTotal >= 200.0) {
                $reductionAmount = 20.0;
                $discountDetails[] = new DiscountDetail(
                    type: 'reduction',
                    name: '满200减20',
                    amount: number_format($reductionAmount, 2, '.', ''),
                    description: '满200元立减20元'
                );
                $totalDiscount += $reductionAmount;
            }

            // 示例：免邮优惠
            if ($productTotal >= 99.0) {
                $discountDetails[] = new DiscountDetail(
                    type: 'free-freight',
                    name: '满99免邮',
                    amount: '0.00',
                    description: '满99元包邮'
                );
            }

            $this->logger->debug('促销优惠计算完成', [
                'user_id' => $user->getUserIdentifier(),
                'product_total' => $productTotal,
                'total_discount' => $totalDiscount,
                'discount_count' => count($discountDetails),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('促销优惠计算失败', [
                'user_id' => $user->getUserIdentifier(),
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'discountAmount' => number_format($totalDiscount, 2, '.', ''),
            'discountDetails' => $discountDetails,
        ];
    }

    public function calculateShippingFee(UserInterface $user, array $cartItems, ?string $freightId = null): string
    {
        if ([] === $cartItems) {
            return '0.00';
        }

        try {
            // 准备SKU列表用于运费计算
            $skus = array_map(fn (CartItem $item) => $item->getSku(), $cartItems);

            if (null === $freightId) {
                // 默认运费
                return '10.00';
            }

            // 调用运费计算服务
            $freightPrice = $this->priceService->findFreightPriceBySkus($freightId, $skus);

            if (null === $freightPrice) {
                return '10.00'; // 默认运费
            }

            return number_format((float) $freightPrice->getPrice(), 2, '.', '');
        } catch (\Throwable $e) {
            $this->logger->error('运费计算失败', [
                'user_id' => $user->getUserIdentifier(),
                'freight_id' => $freightId,
                'error' => $e->getMessage(),
            ]);

            return '10.00'; // 默认运费
        }
    }

    public function checkPriceChanges(array $cartItems): array
    {
        $priceChanges = [];

        foreach ($cartItems as $cartItem) {
            $sku = $cartItem->getSku();

            // 获取当前商品价格
            $marketPrice = $sku->getMarketPrice();
            if (null === $marketPrice) {
                continue;
            }

            // 这里可以比较购物车中存储的价格与当前价格
            // 由于CartItem实体中没有存储价格，这里提供框架结构
            // 实际应用中可以在CartItem中添加price字段来存储加入购物车时的价格

            // 示例逻辑：假设有价格变动检测机制

            $oldPrice = $marketPrice; // 临时使用相同价格

            if ($oldPrice !== $marketPrice) {
                $priceChanges[$sku->getId()] = [
                    'oldPrice' => (string) $oldPrice,
                    'newPrice' => (string) $marketPrice,
                ];
            }
        }

        return $priceChanges;
    }

    private function addAmounts(string $amount1, string $amount2): string
    {
        return bcadd((string) (float) $amount1, (string) (float) $amount2, 2);
    }

    private function subtractAmounts(string $amount1, string $amount2): string
    {
        $result = bcsub((string) (float) $amount1, (string) (float) $amount2, 2);

        // 确保结果不为负数
        return bccomp($result, '0.00', 2) < 0 ? '0.00' : $result;
    }
}
