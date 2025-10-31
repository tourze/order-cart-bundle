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
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Interface\CartManagerInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '更新购物车商品数量')]
#[MethodExpose(method: 'UpdateCartQuantity')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class UpdateCartQuantity extends LockableProcedure
{
    #[MethodParam(description: '购物车商品ID')]
    public string $cartItemId = '';

    #[MethodParam(description: '新数量')]
    public int $quantity = 1;

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

            $this->procedureLogger->info('更新购物车商品数量', [
                'user_id' => $user->getUserIdentifier(),
                'cart_item_id' => $this->cartItemId,
                'new_quantity' => $this->quantity,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $this->cartManager->updateQuantity($user, $this->cartItemId, $this->quantity);

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    1,
                    $totalItems,
                    $totalQuantity,
                    sprintf('商品数量已更新为%d', $this->quantity)
                );

                $this->procedureLogger->info('更新购物车商品数量完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return $response->toArray();
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

    private function validateInput(): void
    {
        if ('' === $this->cartItemId || '' === trim($this->cartItemId)) {
            throw CartValidationException::invalidCartItemId();
        }

        if ($this->quantity <= 0 || $this->quantity > 999) {
            throw CartValidationException::invalidQuantity();
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
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'skuId' => 1,
            'quantity' => 5,
            'selected' => true,
            'metadata' => ['color' => 'red', 'size' => 'M'],
            'createTime' => '2023-01-01T12:00:00+00:00',
            'updateTime' => '2023-01-01T12:05:00+00:00',
            'sku' => [
                'id' => 1,
                'name' => '商品名称',
                'price' => 99.99,
                'stock' => 100,
            ],
        ];
    }
}
