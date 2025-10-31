<?php

namespace Tourze\OrderCartBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\ProductCoreBundle\Entity\Sku;

#[WithMonologChannel(channel: 'order_cart')]
final readonly class CartCacheService
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 清除用户购物车相关的所有缓存
     */
    public function clearUserCartCache(UserInterface $user): void
    {
        try {
            $userCachePattern = sprintf('cart_total:%s:*', $user->getUserIdentifier());

            if ($this->cache instanceof TagAwareCacheInterface) {
                $tag = sprintf('user_cart_%s', $user->getUserIdentifier());
                $this->cache->invalidateTags([$tag]);

                $this->logger->info('已清除用户购物车缓存（通过标签）', [
                    'user_id' => $user->getUserIdentifier(),
                    'tag' => $tag,
                ]);
            } else {
                $this->logger->info('请求清除用户购物车缓存', [
                    'user_id' => $user->getUserIdentifier(),
                    'pattern' => $userCachePattern,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('清除用户购物车缓存失败', [
                'user_id' => $user->getUserIdentifier(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除与特定SKU相关的购物车缓存
     */
    public function clearSkuRelatedCache(Sku $sku): void
    {
        try {
            if ($this->cache instanceof TagAwareCacheInterface) {
                $tag = sprintf('sku_%s', $sku->getId());
                $this->cache->invalidateTags([$tag]);

                $this->logger->info('已清除SKU相关的购物车缓存（通过标签）', [
                    'sku_id' => $sku->getId(),
                    'tag' => $tag,
                ]);
            } else {
                $this->logger->info('请求清除SKU相关的购物车缓存', [
                    'sku_id' => $sku->getId(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('清除SKU相关购物车缓存失败', [
                'sku_id' => $sku->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清除特定的购物车总价缓存
     *
     * @param array<CartItem> $cartItems
     */
    public function clearSpecificCartCache(UserInterface $user, array $cartItems, ?string $freightId = null): void
    {
        try {
            $itemIds = array_map(fn (CartItem $item) => $item->getId(), $cartItems);
            sort($itemIds);

            $keyComponents = [
                'cart_total',
                $user->getUserIdentifier(),
                md5(implode(',', $itemIds)),
                $freightId ?? 'no_freight',
            ];

            $cacheKey = implode(':', $keyComponents);

            // 直接删除缓存项
            $this->cache->delete($cacheKey);

            $this->logger->info('已清除特定购物车缓存', [
                'user_id' => $user->getUserIdentifier(),
                'cache_key' => $cacheKey,
                'item_count' => count($cartItems),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('清除特定购物车缓存失败', [
                'user_id' => $user->getUserIdentifier(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 生成缓存标签
     *
     * @param array<CartItem> $cartItems
     * @return array<string>
     */
    public function generateCacheTags(UserInterface $user, array $cartItems): array
    {
        $tags = [
            sprintf('user_cart_%s', $user->getUserIdentifier()),
        ];

        foreach ($cartItems as $cartItem) {
            $sku = $cartItem->getSku();
            $tags[] = sprintf('sku_%s', $sku->getId());
        }

        return array_unique($tags);
    }

    /**
     * 清除所有购物车相关缓存
     */
    public function clearAllCartCache(): void
    {
        try {
            if ($this->cache instanceof TagAwareCacheInterface) {
                $this->cache->invalidateTags(['cart_total']);

                $this->logger->info('已清除所有购物车缓存（通过标签）');
            } else {
                $this->logger->info('请求清除所有购物车缓存');
            }
        } catch (\Throwable $e) {
            $this->logger->error('清除所有购物车缓存失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 获取缓存统计信息
     *
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        return [
            'cache_type' => get_class($this->cache),
            'supports_tags' => $this->cache instanceof TagAwareCacheInterface,
            'supports_delete' => true,
        ];

        // Note: getStats is not part of CacheInterface, only available on specific implementations
    }
}
