<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\DTO;

use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\OrderCartBundle\Exception\InvalidArgumentException;

final readonly class CartItemDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $id,
        private ProductDTO $product,
        private int $quantity,
        private bool $selected,
        private array $metadata,
        private ?\DateTimeInterface $createTime = null,
        private ?\DateTimeInterface $updateTime = null,
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than 0');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProduct(): ProductDTO
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public static function fromEntity(CartItem $cartItem): self
    {
        $sku = $cartItem->getSku();

        $marketPrice = $sku->getMarketPrice();
        $price = null !== $marketPrice ? sprintf('%.2f', $marketPrice) : '0.00';

        $product = new ProductDTO(
            skuId: $sku->getId(),
            name: $sku->getFullName(),
            price: $price,
            stock: 0,
            isActive: $sku->isValid() ?? true,
            attributes: [
                'gtin' => $sku->getGtin(),
                'unit' => $sku->getUnit(),
                'thumbs' => $sku->getThumbs(),
                'isBundle' => $sku->isBundle(),
                'needConsignee' => $sku->isNeedConsignee(),
            ]
        );

        $id = $cartItem->getId();
        if (null === $id) {
            throw new InvalidArgumentException('CartItem must have an ID to create DTO');
        }

        return new self(
            id: $id,
            product: $product,
            quantity: $cartItem->getQuantity(),
            selected: $cartItem->isSelected(),
            metadata: $cartItem->getMetadata(),
            createTime: $cartItem->getCreateTime(),
            updateTime: $cartItem->getUpdateTime()
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['product'])) {
            throw new InvalidArgumentException('Missing product data');
        }

        if (!\is_array($data['product'])) {
            throw new InvalidArgumentException('Invalid product data');
        }

        /** @var array<string, mixed> $productData */
        $productData = $data['product'];
        $product = ProductDTO::fromArray($productData);

        if (!isset($data['id']) || !\is_string($data['id'])) {
            throw new InvalidArgumentException('Missing or invalid id');
        }

        if (!isset($data['quantity']) || !\is_int($data['quantity'])) {
            throw new InvalidArgumentException('Missing or invalid quantity');
        }

        /** @var array<string, mixed> $metadata */
        $metadata = \is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

        return new self(
            id: $data['id'],
            product: $product,
            quantity: $data['quantity'],
            selected: (bool) ($data['selected'] ?? true),
            metadata: $metadata,
            createTime: isset($data['createTime']) && \is_string($data['createTime']) ? new \DateTime($data['createTime']) : null,
            updateTime: isset($data['updateTime']) && \is_string($data['updateTime']) ? new \DateTime($data['updateTime']) : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product' => $this->product->toArray(),
            'quantity' => $this->quantity,
            'selected' => $this->selected,
            'metadata' => $this->metadata,
            'createTime' => $this->createTime?->format('Y-m-d H:i:s'),
            'updateTime' => $this->updateTime?->format('Y-m-d H:i:s'),
        ];
    }
}
