<?php

namespace Tourze\OrderCartBundle\Procedure;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderCartBundle\DTO\CartTotalResponse;
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
    #[MethodParam(description: '运费模板ID（可选）')]
    public ?string $freightId = null;

    #[MethodParam(description: '是否只计算已选中商品')]
    public bool $onlySelected = true;

    public function __construct(
        private readonly CartItemRepository $cartItemRepository,
        private readonly PriceCalculationServiceInterface $priceCalculationService,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        try {
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('开始计算购物车总价', [
                'user_id' => $user->getUserIdentifier(),
                'freight_id' => $this->freightId,
                'only_selected' => $this->onlySelected,
            ]);
            // 获取用户的购物车项目
            $cartItems = $this->getCartItems($user);

            if ([] === $cartItems) {
                $emptyResponse = CartTotalResponse::success('0.00', '0.00', '0.00', '0.00', '0.00');

                $this->procedureLogger->info('购物车为空，返回零价格', [
                    'user_id' => $user->getUserIdentifier(),
                ]);

                return $emptyResponse->toArray();
            }

            // 计算购物车总价
            $response = $this->priceCalculationService->calculateCartTotal(
                user: $user,
                cartItems: $cartItems,
                freightId: $this->freightId
            );

            $this->procedureLogger->info('购物车总价计算完成', [
                'user_id' => $user->getUserIdentifier(),
                'success' => $response->success,
                'original_amount' => $response->originalAmount,
                'total_amount' => $response->totalAmount,
                'discount_count' => $response->getDiscountCount(),
                'has_free_shipping' => $response->hasFreeShipping(),
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            $userId = isset($user) ? $user->getUserIdentifier() : 'unknown';
            $this->procedureLogger->error('购物车总价计算失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartTotalResponse::failure('计算失败: ' . $e->getMessage());

            return $errorResponse->toArray();
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
    private function getCartItems(UserInterface $user): array
    {
        if ($this->onlySelected) {
            return $this->cartItemRepository->findSelectedByUser($user);
        }

        return $this->cartItemRepository->findByUser($user);
    }
}
