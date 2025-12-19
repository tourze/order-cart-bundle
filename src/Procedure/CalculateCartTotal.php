<?php

namespace Tourze\OrderCartBundle\Procedure;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderCartBundle\DTO\CartTotalResponse;
use Tourze\OrderCartBundle\Param\CalculateCartTotalParam;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\PriceCalculationServiceInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '计算购物车总价格')]
#[MethodExpose(method: 'CalculateCartTotal')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class CalculateCartTotal extends BaseProcedure
{
    public function __construct(
        private readonly CartItemRepository $cartItemRepository,
        private readonly PriceCalculationServiceInterface $priceCalculationService,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param CalculateCartTotalParam $param
     */
    public function execute(CalculateCartTotalParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('开始计算购物车总价', [
                'user_id' => $user->getUserIdentifier(),
                'freight_id' => $param->freightId,
                'only_selected' => $param->onlySelected,
            ]);
            // 获取用户的购物车项目
            $cartItems = $this->getCartItems($user, $param);

            if ([] === $cartItems) {
                $emptyResponse = CartTotalResponse::success('0.00', '0.00', '0.00', '0.00', '0.00');

                $this->procedureLogger->info('购物车为空，返回零价格', [
                    'user_id' => $user->getUserIdentifier(),
                ]);

                return new ArrayResult($emptyResponse->toArray());
            }

            // 计算购物车总价
            $response = $this->priceCalculationService->calculateCartTotal(
                user: $user,
                cartItems: $cartItems,
                freightId: $param->freightId
            );

            $this->procedureLogger->info('购物车总价计算完成', [
                'user_id' => $user->getUserIdentifier(),
                'success' => $response->success,
                'original_amount' => $response->originalAmount,
                'total_amount' => $response->totalAmount,
                'discount_count' => $response->getDiscountCount(),
                'has_free_shipping' => $response->hasFreeShipping(),
            ]);

            return new ArrayResult($response->toArray());
        } catch (\Throwable $e) {
            $userId = isset($user) ? $user->getUserIdentifier() : 'unknown';
            $this->procedureLogger->error('购物车总价计算失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartTotalResponse::failure('计算失败: ' . $e->getMessage());

            return new ArrayResult($errorResponse->toArray());
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }

    /**
     * @return array<CartItem>
     */
    private function getCartItems(UserInterface $user, CalculateCartTotalParam $param): array
    {
        if ($param->onlySelected) {
            return $this->cartItemRepository->findSelectedByUser($user);
        }

        return $this->cartItemRepository->findByUser($user);
    }
}
