<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\ProductDTO;
use Tourze\OrderCartBundle\Exception\InvalidArgumentException;

/**
 * @internal
 */
#[CoversClass(ProductDTO::class)]
final class ProductDTOTest extends TestCase
{
    public function testDTOCreation(): void
    {
        $dto = new ProductDTO(
            skuId: '123',
            name: 'Test Product',
            price: '99.99',
            stock: 10,
            isActive: true,
            attributes: ['color' => 'blue', 'size' => 'M']
        );

        $this->assertEquals('123', $dto->getSkuId());
        $this->assertEquals('Test Product', $dto->getName());
        $this->assertEquals(99.99, $dto->getPrice());
        $this->assertEquals(10, $dto->getStock());
        $this->assertTrue($dto->isActive());
        $this->assertEquals(['color' => 'blue', 'size' => 'M'], $dto->getAttributes());
    }

    public function testDTOWithEmptyAttributes(): void
    {
        $dto = new ProductDTO(
            skuId: '456',
            name: 'Simple Product',
            price: '19.99',
            stock: 5,
            isActive: false
        );

        $this->assertEquals('456', $dto->getSkuId());
        $this->assertEquals('Simple Product', $dto->getName());
        $this->assertEquals(19.99, $dto->getPrice());
        $this->assertEquals(5, $dto->getStock());
        $this->assertFalse($dto->isActive());
        $this->assertEquals([], $dto->getAttributes());
    }

    public function testNegativePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price cannot be negative');

        new ProductDTO(
            skuId: '123',
            name: 'Test Product',
            price: '-10.00',
            stock: 10,
            isActive: true
        );
    }

    public function testNegativeStock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock cannot be negative');

        new ProductDTO(
            skuId: '123',
            name: 'Test Product',
            price: '99.99',
            stock: -1,
            isActive: true
        );
    }

    public function testZeroPrice(): void
    {
        $dto = new ProductDTO(
            skuId: '123',
            name: 'Free Product',
            price: '0.00',
            stock: 10,
            isActive: true
        );

        $this->assertEquals(0.0, $dto->getPrice());
    }

    public function testZeroStock(): void
    {
        $dto = new ProductDTO(
            skuId: '123',
            name: 'Out of Stock Product',
            price: '99.99',
            stock: 0,
            isActive: true
        );

        $this->assertEquals(0, $dto->getStock());
    }

    public function testFromArray(): void
    {
        $data = [
            'skuId' => '789',
            'name' => 'Array Product',
            'price' => '49.99',
            'stock' => 20,
            'isActive' => true,
            'attributes' => ['brand' => 'TestBrand', 'weight' => '1kg'],
        ];

        $dto = ProductDTO::fromArray($data);

        $this->assertEquals('789', $dto->getSkuId());
        $this->assertEquals('Array Product', $dto->getName());
        $this->assertEquals(49.99, $dto->getPrice());
        $this->assertEquals(20, $dto->getStock());
        $this->assertTrue($dto->isActive());
        $this->assertEquals(['brand' => 'TestBrand', 'weight' => '1kg'], $dto->getAttributes());
    }

    public function testFromArrayWithMissingAttributes(): void
    {
        $data = [
            'skuId' => '789',
            'name' => 'Minimal Product',
            'price' => '29.99',
            'stock' => 15,
            'isActive' => false,
        ];

        $dto = ProductDTO::fromArray($data);

        $this->assertEquals('789', $dto->getSkuId());
        $this->assertEquals('Minimal Product', $dto->getName());
        $this->assertEquals(29.99, $dto->getPrice());
        $this->assertEquals(15, $dto->getStock());
        $this->assertFalse($dto->isActive());
        $this->assertEquals([], $dto->getAttributes());
    }

    public function testToArray(): void
    {
        $dto = new ProductDTO(
            skuId: '456',
            name: 'Export Product',
            price: '79.99',
            stock: 8,
            isActive: true,
            attributes: ['category' => 'electronics']
        );

        $expected = [
            'skuId' => '456',
            'name' => 'Export Product',
            'price' => '79.99',
            'stock' => 8,
            'isActive' => true,
            'mainThumb' => null,
            'attributes' => ['category' => 'electronics'],
        ];

        $this->assertEquals($expected, $dto->toArray());
    }
}
