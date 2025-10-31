<?php

namespace Tourze\OrderCartBundle\DTO;

final readonly class DiscountDetail implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $name,
        public string $amount,
        public ?string $description = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'amount' => $this->amount,
            'description' => $this->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getAmountAsFloat(): float
    {
        return (float) $this->amount;
    }

    public function isReduction(): bool
    {
        return 'reduction' === $this->type;
    }

    public function isDiscount(): bool
    {
        return 'discount' === $this->type;
    }

    public function isFreeFreight(): bool
    {
        return 'free-freight' === $this->type;
    }

    public function isCoupon(): bool
    {
        return 'coupon' === $this->type;
    }
}
