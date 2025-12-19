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
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Param\ToggleCartItemSelectionParam;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '切换购物车商品选中状态')]
#[MethodExpose(method: 'ToggleCartItemSelection')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class ToggleCartItemSelection extends LockableProcedure
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerInterface $cartManager,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param ToggleCartItemSelectionParam $param
     */
    public function execute(ToggleCartItemSelectionParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $this->validateInput($param);
            $user = $this->getCurrentUser();

            $cartItemIds = is_array($param->cartItemIds) ? $param->cartItemIds : [$param->cartItemIds];

            $this->procedureLogger->info('切换购物车商品选中状态', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($cartItemIds),
                'selected' => $param->selected,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $affectedCount = count($cartItemIds);
                if (1 === $affectedCount) {
                    $this->cartManager->updateSelection($user, $cartItemIds[0], $param->selected);
                } else {
                    $this->cartManager->batchUpdateSelection($user, $cartItemIds, $param->selected);
                }

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    $affectedCount,
                    $totalItems,
                    $totalQuantity,
                    sprintf('%s了%d个商品', $param->selected ? '选中' : '取消选中', $affectedCount)
                );

                $this->procedureLogger->info('切换购物车商品选中状态完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return new ArrayResult($response->toArray());
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('切换购物车商品选中状态失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartOperationResponse::failure('操作失败: ' . $e->getMessage());

            return new ArrayResult($errorResponse->toArray());
        }
    }

    private function validateInput(ToggleCartItemSelectionParam $param): void
    {
        $cartItemIds = is_array($param->cartItemIds) ? $param->cartItemIds : [$param->cartItemIds];

        if ([] === $cartItemIds) {
            throw CartValidationException::emptyItemIds();
        }

        if (count($cartItemIds) > 200) {
            throw CartValidationException::tooManyItems();
        }

        foreach ($cartItemIds as $itemId) {
            if ('' === $itemId || '' === trim($itemId)) {
                throw CartValidationException::invalidItemId();
            }
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }
}
