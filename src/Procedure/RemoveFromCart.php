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
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '从购物车移除单个商品')]
#[MethodExpose(method: 'RemoveFromCart')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class RemoveFromCart extends LockableProcedure
{
    #[MethodParam(description: '购物车商品ID')]
    public string $cartItemId = '';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerInterface $cartManager,
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

            $this->procedureLogger->info('从购物车移除商品', [
                'user_id' => $user->getUserIdentifier(),
                'cart_item_id' => $this->cartItemId,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $this->cartManager->removeItem($user, $this->cartItemId);

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    1,
                    $totalItems,
                    $totalQuantity,
                    '商品已从购物车移除'
                );

                $this->procedureLogger->info('从购物车移除商品完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return $response->toArray();
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('从购物车移除商品失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartOperationResponse::failure('操作失败: ' . $e->getMessage());

            return $errorResponse->toArray();
        }
    }

    private function validateInput(): void
    {
        if ('' === $this->cartItemId || '' === trim($this->cartItemId)) {
            throw CartValidationException::invalidCartItemId();
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMockResult(): array
    {
        return [
            'success' => true,
            'message' => '商品已从购物车移除',
        ];
    }
}
