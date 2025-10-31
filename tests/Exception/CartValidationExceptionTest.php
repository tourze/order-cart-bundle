<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CartValidationException::class)]
final class CartValidationExceptionTest extends AbstractExceptionTestCase
{
    public function testEmptyItemIds(): void
    {
        $exception = CartValidationException::emptyItemIds();

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('项目ID列表不能为空', $exception->getMessage());
    }

    public function testTooManyItems(): void
    {
        $maxItems = 150;
        $exception = CartValidationException::tooManyItems($maxItems);

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals("一次最多操作{$maxItems}个项目", $exception->getMessage());
    }

    public function testTooManyItemsWithDefault(): void
    {
        $exception = CartValidationException::tooManyItems();

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals('一次最多操作200个项目', $exception->getMessage());
    }

    public function testInvalidItemId(): void
    {
        $exception = CartValidationException::invalidItemId();

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals('项目ID必须为非空字符串', $exception->getMessage());
    }

    public function testDuplicateItemIds(): void
    {
        $exception = CartValidationException::duplicateItemIds();

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals('项目ID列表包含重复项', $exception->getMessage());
    }

    public function testItemsNotFound(): void
    {
        $missingIds = ['item1', 'item2', 'item3'];
        $exception = CartValidationException::itemsNotFound($missingIds);

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals('部分购物车项目不存在或不属于当前用户: item1, item2, item3', $exception->getMessage());
    }

    public function testTooManyCartItems(): void
    {
        $maxItems = 100;
        $exception = CartValidationException::tooManyCartItems($maxItems);

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals("购物车项目总数不能超过{$maxItems}个", $exception->getMessage());
    }

    public function testTooManyCartItemsWithDefault(): void
    {
        $exception = CartValidationException::tooManyCartItems();

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals('购物车项目总数不能超过200个', $exception->getMessage());
    }

    public function testTotalQuantityTooHigh(): void
    {
        $maxQuantity = 5000;
        $exception = CartValidationException::totalQuantityTooHigh($maxQuantity);

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals("购物车商品总数量不能超过{$maxQuantity}个", $exception->getMessage());
    }

    public function testTotalQuantityTooHighWithDefault(): void
    {
        $exception = CartValidationException::totalQuantityTooHigh();

        $this->assertInstanceOf(CartValidationException::class, $exception);
        $this->assertEquals('购物车商品总数量不能超过9999个', $exception->getMessage());
    }
}
