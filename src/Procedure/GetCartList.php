<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Procedure;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\Param\GetCartListParam;
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '获取购物车商品列表')]
#[MethodExpose(method: 'GetCartList')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class GetCartList extends BaseProcedure
{
    public function __construct(
        private readonly CartDataProviderInterface $cartDataProvider,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param GetCartListParam $param
     */
    public function execute(GetCartListParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('获取购物车列表', [
                'user_id' => $user->getUserIdentifier(),
                'selected_only' => $param->selectedOnly,
            ]);

            $cartItems = $param->selectedOnly
                ? $this->cartDataProvider->getSelectedItems($user)
                : $this->cartDataProvider->getCartItems($user);

            $data = array_map(fn (CartItemDTO $item) => $item->toArray(), $cartItems);
            $result = [
                'items' => $data,
                'summary' => $this->cartDataProvider->getCartSummary($user)->toArray(),
            ];

            $this->procedureLogger->info('获取购物车列表完成', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($cartItems),
            ]);

            return new ArrayResult($result);
        } catch (\Throwable $e) {
            $userId = isset($user) ? $user->getUserIdentifier() : 'unknown';
            $this->procedureLogger->error('获取购物车列表失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }
}
