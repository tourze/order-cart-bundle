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

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '设置购物车项目选中状态')]
#[MethodExpose(method: 'SetCartItemChecked')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class SetCartItemChecked extends LockableProcedure
{
    /**
     * @var array<string>
     */
    #[MethodParam(description: '项目ID列表(最多200个)')]
    public array $itemIds = [];

    #[MethodParam(description: '选中状态')]
    public bool $checked = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartItemRepository $cartItemRepository,
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

            $this->procedureLogger->info('设置购物车项目选中状态', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($this->itemIds),
                'checked' => $this->checked,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $items = $this->cartItemRepository->findByUserAndIds($user, $this->itemIds);
                $foundItemIds = array_map(fn ($item) => $item->getId(), $items);
                $missingIds = array_diff($this->itemIds, $foundItemIds);

                if ([] !== $missingIds) {
                    throw CartValidationException::itemsNotFound($missingIds);
                }

                $affectedCount = $this->cartItemRepository->batchUpdateCheckedStatus(
                    $user,
                    $this->itemIds,
                    $this->checked
                );

                $totalItems = $this->cartItemRepository->countByUser($user);
                $totalQuantity = $this->cartItemRepository->getTotalQuantityByUser($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    $affectedCount,
                    $totalItems,
                    $totalQuantity,
                    sprintf('成功%s%d个购物车项目', $this->checked ? '勾选' : '取消勾选', $affectedCount)
                );

                $this->procedureLogger->info('设置购物车项目选中状态完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return $response->toArray();
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
