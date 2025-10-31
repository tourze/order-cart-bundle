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
#[MethodDoc(summary: '切换购物车商品选中状态')]
#[MethodExpose(method: 'ToggleCartItemSelection')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class ToggleCartItemSelection extends LockableProcedure
{
    /**
     * @var array<string>|string
     */
    #[MethodParam(description: '购物车商品ID，单个或多个')]
    public string|array $cartItemIds = '';

    #[MethodParam(description: '是否选中')]
    public bool $selected = true;

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

            $cartItemIds = is_array($this->cartItemIds) ? $this->cartItemIds : [$this->cartItemIds];

            $this->procedureLogger->info('切换购物车商品选中状态', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($cartItemIds),
                'selected' => $this->selected,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $affectedCount = count($cartItemIds);
                if (1 === $affectedCount) {
                    $this->cartManager->updateSelection($user, $cartItemIds[0], $this->selected);
                } else {
                    $this->cartManager->batchUpdateSelection($user, $cartItemIds, $this->selected);
                }

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    $affectedCount,
                    $totalItems,
                    $totalQuantity,
                    sprintf('%s了%d个商品', $this->selected ? '选中' : '取消选中', $affectedCount)
                );

                $this->procedureLogger->info('切换购物车商品选中状态完成', [
                    'success' => $response->success,
                    'affected_count' => $response->affectedCount,
                ]);

                return $response->toArray();
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

            return $errorResponse->toArray();
        }
    }

    private function validateInput(): void
    {
        $cartItemIds = is_array($this->cartItemIds) ? $this->cartItemIds : [$this->cartItemIds];

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

    /**
     * @return array<string, mixed>
     */
    public static function getMockResult(): array
    {
        return [
            'updated' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'skuId' => 1,
                    'quantity' => 2,
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
                ],
            ],
            'message' => '商品已选中',
        ];
    }
}
