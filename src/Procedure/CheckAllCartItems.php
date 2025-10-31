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
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Repository\CartItemRepository;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '全选或取消全选购物车项目')]
#[MethodExpose(method: 'CheckAllCartItems')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class CheckAllCartItems extends BaseProcedure
{
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
            $user = $this->getCurrentUser();
            $this->validateCartLimits($user);

            $this->procedureLogger->info('全选/取消全选购物车项目', [
                'user_id' => $user->getUserIdentifier(),
                'checked' => $this->checked,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $affectedCount = $this->cartItemRepository->updateAllCheckedStatus($user, $this->checked);

                $totalItems = $this->cartItemRepository->countByUser($user);
                $totalQuantity = $this->cartItemRepository->getTotalQuantityByUser($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    $affectedCount,
                    $totalItems,
                    $totalQuantity,
                    sprintf('成功%s所有购物车项目（%d个）', $this->checked ? '勾选' : '取消勾选', $affectedCount)
                );

                $this->procedureLogger->info('全选/取消全选购物车项目完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return $response->toArray();
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('全选/取消全选购物车项目失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartOperationResponse::failure('操作失败: ' . $e->getMessage());

            return $errorResponse->toArray();
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }

    private function validateCartLimits(UserInterface $user): void
    {
        $totalItems = $this->cartItemRepository->countByUser($user);
        if ($totalItems > 200) {
            throw CartValidationException::tooManyCartItems();
        }

        $totalQuantity = $this->cartItemRepository->getTotalQuantityByUser($user);
        if ($totalQuantity > 9999) {
            throw CartValidationException::totalQuantityTooHigh();
        }
    }
}
