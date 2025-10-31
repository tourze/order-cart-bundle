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
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
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
    /**
     * @var array<string>
     */
    #[MethodParam(description: '项目ID列表(最多200个)')]
    public array $itemIds = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartItemRepository $cartItemRepository,
        private readonly CartAddLogService $cartAddLogService,
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
            $this->validateInput();
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('删除购物车项目', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($this->itemIds),
            ]);

            $this->entityManager->beginTransaction();

            try {
                $items = $this->cartItemRepository->findByUserAndIds($user, $this->itemIds);
                $foundItemIds = array_map(fn ($item) => $item->getId(), $items);
                $missingIds = array_diff($this->itemIds, $foundItemIds);

                if ([] !== $missingIds) {
                    throw CartValidationException::itemsNotFound($missingIds);
                }

                // 先标记日志为已删除
                $this->cartAddLogService->batchMarkAsDeleted($this->itemIds);

                // 然后硬删除购物车项
                $affectedCount = $this->cartItemRepository->batchDelete($user, $this->itemIds);

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

                return $response->toArray();
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

            return $errorResponse->toArray();
        }
    }

    private function validateInput(): void
    {
        if ([] === $this->itemIds) {
            throw CartValidationException::emptyItemIds();
        }

        if (count($this->itemIds) > 200) {
            throw CartValidationException::tooManyItems();
        }

        foreach ($this->itemIds as $itemId) {
            if ('' === $itemId) {
                throw CartValidationException::invalidItemId();
            }
        }

        if (count($this->itemIds) !== count(array_unique($this->itemIds))) {
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
