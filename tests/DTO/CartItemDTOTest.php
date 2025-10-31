<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\CartItemDTO;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\OrderCartBundle\Exception\InvalidArgumentException;

/**
 * @internal
 */
#[CoversClass(CartItemDTO::class)]
final class CartItemDTOTest extends TestCase
{
    private ProductDTO $product;

    protected function setUp(): void
    {
        $this->product = new ProductDTO(
            skuId: '123',
            name: 'Test Product',
            price: '99.99',
            stock: 10,
            isActive: true,
            attributes: ['color' => 'blue']
        );
    }

    public function testDTOCreation(): void
    {
        $dto = new CartItemDTO(
            id: '456',
            product: $this->product,
            quantity: 2,
            selected: true,
            metadata: ['source' => 'web']
        );

        $this->assertEquals('456', $dto->getId());
        $this->assertSame($this->product, $dto->getProduct());
        $this->assertEquals(2, $dto->getQuantity());
        $this->assertTrue($dto->isSelected());
        $this->assertEquals(['source' => 'web'], $dto->getMetadata());
        $this->assertNull($dto->getCreateTime());
        $this->assertNull($dto->getUpdateTime());
    }

    public function testDTOWithTimestamps(): void
    {
        $createdAt = new \DateTime('2024-01-01 10:00:00');
        $updatedAt = new \DateTime('2024-01-02 10:00:00');

        $dto = new CartItemDTO(
            id: '456',
            product: $this->product,
            quantity: 1,
            selected: false,
            metadata: [],
            createTime: $createdAt,
            updateTime: $updatedAt
        );

        $this->assertEquals($createdAt, $dto->getCreateTime());
        $this->assertEquals($updatedAt, $dto->getUpdateTime());
    }

    public function testInvalidQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');

        new CartItemDTO(
            id: '456',
            product: $this->product,
            quantity: 0,
            selected: true,
            metadata: []
        );
    }

    public function testNegativeQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');

        new CartItemDTO(
            id: '456',
            product: $this->product,
            quantity: -1,
            selected: true,
            metadata: []
        );
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => '789',
            'product' => [
                'skuId' => '123',
                'name' => 'Test Product',
                'price' => '99.99',
                'stock' => 10,
                'isActive' => true,
                'attributes' => ['color' => 'blue'],
            ],
            'quantity' => 3,
            'selected' => false,
            'metadata' => ['source' => 'mobile'],
            'createTime' => '2024-01-01 10:00:00',
            'updateTime' => '2024-01-02 10:00:00',
        ];

        $dto = CartItemDTO::fromArray($data);

        $this->assertEquals('789', $dto->getId());
        $this->assertEquals('Test Product', $dto->getProduct()->getName());
        $this->assertEquals(3, $dto->getQuantity());
        $this->assertFalse($dto->isSelected());
        $this->assertEquals(['source' => 'mobile'], $dto->getMetadata());
        $createdAt = $dto->getCreateTime();
        $updatedAt = $dto->getUpdateTime();
        $this->assertNotNull($createdAt, 'CreatedAt should not be null');
        $this->assertNotNull($updatedAt, 'UpdatedAt should not be null');
        $this->assertEquals('2024-01-01 10:00:00', $createdAt->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-02 10:00:00', $updatedAt->format('Y-m-d H:i:s'));
    }

    public function testToArray(): void
    {
        $createdAt = new \DateTime('2024-01-01 10:00:00');
        $updatedAt = new \DateTime('2024-01-02 10:00:00');

        $dto = new CartItemDTO(
            id: '456',
            product: $this->product,
            quantity: 2,
            selected: true,
            metadata: ['source' => 'web'],
            createTime: $createdAt,
            updateTime: $updatedAt
        );

        $expected = [
            'id' => '456',
            'product' => [
                'skuId' => '123',
                'name' => 'Test Product',
                'price' => '99.99',
                'stock' => 10,
                'isActive' => true,
                'mainThumb' => null,
                'attributes' => ['color' => 'blue'],
            ],
            'quantity' => 2,
            'selected' => true,
            'metadata' => ['source' => 'web'],
            'createTime' => '2024-01-01 10:00:00',
            'updateTime' => '2024-01-02 10:00:00',
        ];

        $this->assertEquals($expected, $dto->toArray());
    }
}
