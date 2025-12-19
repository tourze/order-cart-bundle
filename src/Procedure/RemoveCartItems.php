<?php

namespace Tourze\OrderCartBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
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
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Param\RemoveCartItemsParam;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\OrderCartBundle\Service\CartAddLogService;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '删除购物车项目')]
#[MethodExpose(method: 'RemoveCartItems')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class RemoveCartItems extends LockableProcedure
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartItemRepository $cartItemRepository,
        private readonly CartAddLogService $cartAddLogService,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param RemoveCartItemsParam $param
     */
    public function execute(RemoveCartItemsParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $this->validateInput($param);
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('删除购物车项目', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($param->itemIds),
            ]);

            $this->entityManager->beginTransaction();

            try {
                $items = $this->cartItemRepository->findByUserAndIds($user, $param->itemIds);
                $foundItemIds = array_map(fn ($item) => $item->getId(), $items);
                $missingIds = array_diff($param->itemIds, $foundItemIds);

                if ([] !== $missingIds) {
                    throw CartValidationException::itemsNotFound($missingIds);
                }

                // 先标记日志为已删除
                $this->cartAddLogService->batchMarkAsDeleted($param->itemIds);

                // 然后硬删除购物车项
                $affectedCount = $this->cartItemRepository->batchDelete($user, $param->itemIds);

                $totalItems = $this->cartItemRepository->countByUser($user);
                $totalQuantity = $this->cartItemRepository->getTotalQuantityByUser($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    $affectedCount,
                    $totalItems,
                    $totalQuantity,
                    sprintf('成功删除%d个购物车项目', $affectedCount)
                );

                $this->procedureLogger->info('删除购物车项目完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return new ArrayResult($response->toArray());
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('删除购物车项目失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartOperationResponse::failure('操作失败: ' . $e->getMessage());

            return new ArrayResult($errorResponse->toArray());
        }
    }

    private function validateInput(RemoveCartItemsParam $param): void
    {
        if ([] === $param->itemIds) {
            throw CartValidationException::emptyItemIds();
        }

        if (count($param->itemIds) > 200) {
            throw CartValidationException::tooManyItems();
        }

        foreach ($param->itemIds as $itemId) {
            if ('' === $itemId) {
                throw CartValidationException::invalidItemId();
            }
        }

        if (count($param->itemIds) !== count(array_unique($param->itemIds))) {
            throw CartValidationException::duplicateItemIds();
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }
}
