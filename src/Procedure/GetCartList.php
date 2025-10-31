<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Procedure;

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
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\Interface\CartDataProviderInterface;

#[MethodTag(name: '购物车管理')]
#[MethodDoc(summary: '获取购物车商品列表')]
#[MethodExpose(method: 'GetCartList')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[WithMonologChannel(channel: 'order_cart')]
final class GetCartList extends BaseProcedure
{
    #[MethodParam(description: '是否只获取已选中的商品')]
    public bool $selectedOnly = false;

    public function __construct(
        private readonly CartDataProviderInterface $cartDataProvider,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    public function execute(): array
    {
        try {
            $user = $this->getCurrentUser();

            $this->procedureLogger->info('获取购物车列表', [
                'user_id' => $user->getUserIdentifier(),
                'selected_only' => $this->selectedOnly,
            ]);

            $cartItems = $this->selectedOnly
                ? $this->cartDataProvider->getSelectedItems($user)
                : $this->cartDataProvider->getCartItems($user);

            $data = array_map(fn (CartItemDTO $item) => $item->toArray(), $cartItems);
            $result = [
                'items' => $data,
                'summary' => $this->cartDataProvider->getCartSummary($user)->toArray(),
            ];

            $this->procedureLogger->info('获取购物车列表完成', [
                'user_id' => $user->getUserIdentifier(),
                'item_count' => count($cartItems),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $userId = isset($user) ? $user->getUserIdentifier() : 'unknown';
            $this->procedureLogger->error('获取购物车列表失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
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
            'items' => [
                [
                    'id' => '123e4567-e89b-12d3-a456-426614174000',
                    'quantity' => 2,
                    'selected' => true,
                    'metadata' => ['color' => 'red', 'size' => 'M'],
                    'createTime' => '2023-01-01 12:00:00',
                    'updateTime' => '2023-01-01 12:00:00',
                    'mainThumb' => 'https://example.com/product1.jpg',
                    'product' => [
                        'skuId' => 1,
                        'name' => '商品名称1',
                        'price' => '99.99',
                        'stock' => 100,
                        'isActive' => true,
                        'attributes' => [
                            'gtin' => '123456789012',
                            'unit' => 'pcs',
                            'thumbs' => [['url' => 'https://example.com/thumb1.jpg']],
                            'isBundle' => false,
                            'needConsignee' => true,
                            'mainImage' => 'https://example.com/product1.jpg',
                        ],
                    ],
                ],
                [
                    'id' => '987e6543-e21b-12d3-a456-426614174000',
                    'quantity' => 1,
                    'selected' => false,
                    'metadata' => [],
                    'createTime' => '2023-01-01 13:00:00',
                    'updateTime' => '2023-01-01 13:00:00',
                    'mainThumb' => 'https://example.com/product2.jpg',
                    'product' => [
                        'skuId' => 2,
                        'name' => '商品名称2',
                        'price' => '59.99',
                        'stock' => 50,
                        'isActive' => true,
                        'attributes' => [
                            'gtin' => '123456789013',
                            'unit' => 'pcs',
                            'thumbs' => [['url' => 'https://example.com/thumb2.jpg']],
                            'isBundle' => false,
                            'needConsignee' => true,
                            'mainImage' => 'https://example.com/product2.jpg',
                        ],
                    ],
                ],
            ],
            'summary' => [
                'totalItems' => 2,
                'selectedItems' => 1,
                'totalAmount' => '259.97',
                'selectedAmount' => '199.98',
            ],
        ];
    }
}
