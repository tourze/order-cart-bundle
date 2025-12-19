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
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;
use Tourze\OrderCartBundle\Param\AddToCartParam;
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
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManagerInterface $cartManager,
        private readonly SkuLoaderInterface $skuLoader,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    /**
     * @phpstan-param AddToCartParam $param
     */
    public function execute(AddToCartParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $this->validateInput($param);
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('添加商品到购物车', [
                'user_id' => $user->getUserIdentifier(),
                'sku_id' => $param->skuId,
                'quantity' => $param->quantity,
            ]);

            $this->entityManager->beginTransaction();

            try {
                $sku = $this->skuLoader->loadSkuByIdentifier($param->skuId);
                if (!$sku instanceof Sku) {
                    throw CartValidationException::skuNotFound($param->skuId);
                }

                $cartItem = $this->cartManager->addItem($user, $sku, $param->quantity, $param->metadata);

                $totalItems = $this->cartManager->getCartItemCount($user);
                $totalQuantity = $this->cartManager->getCartTotalQuantity($user);

                $this->entityManager->commit();

                $response = CartOperationResponse::success(
                    1,
                    $totalItems,
                    $totalQuantity,
                    sprintf('成功添加商品到购物车，数量：%d', $param->quantity)
                );

                $this->procedureLogger->info('添加商品到购物车完成', [
                    'success' => $response->success,
                    'cart_item_id' => $cartItem->getId(),
                ]);

                return new ArrayResult($response->toArray());
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

            return new ArrayResult($errorResponse->toArray());
        }
    }

    private function validateInput(AddToCartParam $param): void
    {
        if ($param->skuId <= 0) {
            throw CartValidationException::invalidSkuId();
        }

        if ($param->quantity <= 0 || $param->quantity > 999) {
            throw CartValidationException::invalidQuantity();
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        assert($user instanceof UserInterface);

        return $user;
    }
}
