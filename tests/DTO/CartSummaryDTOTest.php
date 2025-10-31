<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\CartSummaryDTO;
use Tourze\OrderCartBundle\Exception\InvalidArgumentException;

/**
 * @internal
 */
#[CoversClass(CartSummaryDTO::class)]
final class CartSummaryDTOTest extends TestCase
{
    public function testDTOCreation(): void
    {
        $dto = new CartSummaryDTO(
            totalItems: 5,
            selectedItems: 3,
            totalAmount: '199.99',
            selectedAmount: '99.99'
        );

        $this->assertEquals(5, $dto->getTotalItems());
        $this->assertEquals(3, $dto->getSelectedItems());
        $this->assertEquals('199.99', $dto->getTotalAmount());
        $this->assertEquals('99.99', $dto->getSelectedAmount());
    }

    public function testDTOIsImmutable(): void
    {
        $dto = new CartSummaryDTO(
            totalItems: 5,
            selectedItems: 3,
            totalAmount: '199.99',
            selectedAmount: '99.99'
        );

        // DTO should be immutable with readonly properties
        $reflection = new \ReflectionClass($dto);

        // 验证所有属性都是只读的
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }

        // 验证类是 final 的
        $this->assertTrue($reflection->isFinal(), 'CartSummaryDTO should be final');
    }

    public function testDTOFromArray(): void
    {
        $data = [
            'totalItems' => 5,
            'selectedItems' => 3,
            'totalAmount' => '199.99',
            'selectedAmount' => '99.99',
        ];

        $dto = CartSummaryDTO::fromArray($data);

        $this->assertEquals(5, $dto->getTotalItems());
        $this->assertEquals(3, $dto->getSelectedItems());
        $this->assertEquals('199.99', $dto->getTotalAmount());
        $this->assertEquals('99.99', $dto->getSelectedAmount());
    }

    public function testDTOToArray(): void
    {
        $dto = new CartSummaryDTO(
            totalItems: 5,
            selectedItems: 3,
            totalAmount: '199.99',
            selectedAmount: '99.99'
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'totalItems' => 5,
            'selectedItems' => 3,
            'totalAmount' => '199.99',
            'selectedAmount' => '99.99',
        ], $array);
    }

    public function testDTOValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total items cannot be negative');

        new CartSummaryDTO(
            totalItems: -1,
            selectedItems: 0,
            totalAmount: '0.00',
            selectedAmount: '0.00'
        );
    }
}
