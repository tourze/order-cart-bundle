<?php

declare(strict_types=1);

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
use Tourze\OrderCartBundle\Interface\CartManagerInterface;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductServiceContracts\SkuLoaderInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '添加商品到购物车')]
#[MethodExpose(method: 'AddToCart')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class AddToCart extends LockableProcedure
{
    #[MethodParam(description: 'SKU ID')]
    public string $skuId;

    #[MethodParam(description: '商品数量')]
    public int $quantity = 1;

    /**
     * @var array<string, mixed>
     */
    #[MethodParam(description: '商品元数据')]
    public array $metadata = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerInterface $cartManager,
        private readonly SkuLoaderInterface $skuLoader,
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

            $this->procedureLogger->info('添加商品到购物车', [
                'user_id' => $user->getUserIdentifier(),
                'sku_id' => $this->skuId,
                'quantity' => $this->quantity,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $sku = $this->skuLoader->loadSkuByIdentifier($this->skuId);
                if (null === $sku) {
                    throw CartValidationException::skuNotFound($this->skuId);
                }

                assert($sku instanceof Sku);
                $cartItem = $this->cartManager->addItem($user, $sku, $this->quantity, $this->metadata);

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    1,
                    $totalItems,
                    $totalQuantity,
                    sprintf('成功添加商品到购物车，数量：%d', $this->quantity)
                );

                $this->procedureLogger->info('添加商品到购物车完成', [
                    'success' => $response->success,
                    'cart_item_id' => $cartItem->getId(),
                ]);

                return $response->toArray();
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->procedureLogger->error('添加商品到购物车失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorResponse = CartOperationResponse::failure('操作失败: ' . $e->getMessage());

            return $errorResponse->toArray();
        }
    }

    private function validateInput(): void
    {
        if ($this->skuId <= 0) {
            throw CartValidationException::invalidSkuId();
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
            'quantity' => 2,
            'selected' => true,
            'metadata' => ['color' => 'red', 'size' => 'M'],
            'createTime' => '2023-01-01T12:00:00+00:00',
            'updateTime' => '2023-01-01T12:00:00+00:00',
            'sku' => [
                'id' => 1,
                'name' => '商品名称',
                'price' => 99.99,
                'stock' => 100,
            ],
        ];
    }
}
