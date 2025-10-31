<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

final class CartValidationException extends \RuntimeException
{
    public static function emptyItemIds(): self
    {
        return new self('项目ID列表不能为空');
    }

    public static function tooManyItems(int $maxItems = 200): self
    {
        return new self("一次最多操作{$maxItems}个项目");
    }

    public static function invalidItemId(): self
    {
        return new self('项目ID必须为非空字符串');
    }

    public static function duplicateItemIds(): self
    {
        return new self('项目ID列表包含重复项');
    }

    /**
     * @param array<string> $missingIds
     */
    public static function itemsNotFound(array $missingIds): self
    {
        return new self('部分购物车项目不存在或不属于当前用户: ' . implode(', ', $missingIds));
    }

    public static function tooManyCartItems(int $maxItems = 200): self
    {
        return new self("购物车项目总数不能超过{$maxItems}个");
    }

    public static function totalQuantityTooHigh(int $maxQuantity = 9999): self
    {
        return new self("购物车商品总数量不能超过{$maxQuantity}个");
    }

    public static function skuNotFound(string $skuId): self
    {
        return new self("SKU {$skuId} 不存在");
    }

    public static function invalidSkuId(): self
    {
        return new self('SKU ID 必须为正整数');
    }

    public static function invalidQuantity(): self
    {
        return new self('商品数量必须在 1-999 之间');
    }

    public static function invalidCartItemId(): self
    {
        return new self('购物车商品ID不能为空');
    }
}
