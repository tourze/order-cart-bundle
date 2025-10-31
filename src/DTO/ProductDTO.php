<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\DTO;

use Tourze\OrderCartBundle\Exception\InvalidArgumentException;

final readonly class ProductDTO
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private string $skuId,
        private string $name,
        private string $price,
        private int $stock,
        private bool $isActive,
        private ?string $mainThumb = null,
        private array $attributes = [],
    ) {
        if (bccomp((string) (float) $price, '0.00', 2) < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }
        if ($stock < 0) {
            throw new InvalidArgumentException('Stock cannot be negative');
        }
    }

    public function getSkuId(): string
    {
        return $this->skuId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getMainThumb(): ?string
    {
        return $this->mainThumb;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['skuId']) || !\is_string($data['skuId'])) {
            throw new InvalidArgumentException('Missing or invalid skuId');
        }

        if (!isset($data['name']) || !\is_string($data['name'])) {
            throw new InvalidArgumentException('Missing or invalid name');
        }

        if (!isset($data['price']) || !\is_string($data['price'])) {
            throw new InvalidArgumentException('Missing or invalid price');
        }

        if (!isset($data['stock']) || !\is_int($data['stock'])) {
            throw new InvalidArgumentException('Missing or invalid stock');
        }

        /** @var array<string, mixed> $attributes */
        $attributes = \is_array($data['attributes'] ?? null) ? $data['attributes'] : [];

        return new self(
            skuId: $data['skuId'],
            name: $data['name'],
            price: $data['price'],
            stock: $data['stock'],
            isActive: (bool) ($data['isActive'] ?? true),
            mainThumb: isset($data['mainThumb']) && \is_string($data['mainThumb']) ? $data['mainThumb'] : null,
            attributes: $attributes
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'skuId' => $this->skuId,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock,
            'isActive' => $this->isActive,
            'mainThumb' => $this->mainThumb,
            'attributes' => $this->attributes,
        ];
    }
}
