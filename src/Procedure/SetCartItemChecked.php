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
use Tourze\OrderCartBundle\Param\SetCartItemCheckedParam;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '设置购物车项目选中状态')]
#[MethodExpose(method: 'SetCartItemChecked')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class SetCartItemChecked extends LockableProcedure
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartItemRepository $cartItemRepository,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param SetCartItemCheckedParam $param
     */
    public function execute(SetCartItemCheckedParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $this->validateInput($param);
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('设置购物车项目选中状态', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($param->itemIds),
                'checked' => $param->checked,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $items = $this->cartItemRepository->findByUserAndIds($user, $param->itemIds);
                $foundItemIds = array_map(fn ($item) => $item->getId(), $items);
                $missingIds = array_diff($param->itemIds, $foundItemIds);

                if ([] !== $missingIds) {
                    throw CartValidationException::itemsNotFound($missingIds);
                }

                $affectedCount = $this->cartItemRepository->batchUpdateCheckedStatus(
                    $user,
                    $param->itemIds,
                    $param->checked
                );

                $totalItems = $this->cartItemRepository->countByUser($user);
                $totalQuantity = $this->cartItemRepository->getTotalQuantityByUser($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    $affectedCount,
                    $totalItems,
                    $totalQuantity,
                    sprintf('成功%s%d个购物车项目', $param->checked ? '勾选' : '取消勾选', $affectedCount)
                );

                $this->procedureLogger->info('设置购物车项目选中状态完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return new ArrayResult($response->toArray());
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('设置购物车项目选中状态失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartOperationResponse::failure('操作失败: ' . $e->getMessage());

            return new ArrayResult($errorResponse->toArray());
        }
    }

    private function validateInput(SetCartItemCheckedParam $param): void
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
