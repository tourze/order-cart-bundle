<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
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
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Param\UpdateCartQuantityParam;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '更新购物车商品数量')]
#[MethodExpose(method: 'UpdateCartQuantity')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class UpdateCartQuantity extends LockableProcedure
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerInterface $cartManager,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param UpdateCartQuantityParam $param
     */
    public function execute(UpdateCartQuantityParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $this->validateInput($param);
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('更新购物车商品数量', [
                'user_id' => $user->getUserIdentifier(),
                'cart_item_id' => $param->cartItemId,
                'new_quantity' => $param->quantity,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $this->cartManager->updateQuantity($user, $param->cartItemId, $param->quantity);

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    1,
                    $totalItems,
                    $totalQuantity,
                    sprintf('商品数量已更新为%d', $param->quantity)
                );

                $this->procedureLogger->info('更新购物车商品数量完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return new ArrayResult($response->toArray());
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('更新购物车商品数量失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(UpdateCartQuantityParam $param): void
    {
        if ('' === $param->cartItemId || '' === trim($param->cartItemId)) {
            throw CartValidationException::invalidCartItemId();
        }

        if ($param->quantity <= 0 || $param->quantity > 999) {
            throw CartValidationException::invalidQuantity();
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }
}
