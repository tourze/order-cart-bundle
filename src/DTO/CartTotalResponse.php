<?php

namespace Tourze\OrderCartBundle\DTO;

final readonly class CartTotalResponse implements \JsonSerializable
{
    /**
     * @param array<DiscountDetail> $discountDetails
     */
    public function __construct(
        public string $originalAmount,
        public string $productAmount,
        public string $discountAmount,
        public string $shippingFee,
        public string $totalAmount,
        public array $discountDetails = [],
        public bool $success = true,
        public ?string $message = null,
        public ?string $currency = 'CNY',
        public ?\DateTimeImmutable $calculatedAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'originalAmount' => $this->originalAmount,
            'productAmount' => $this->productAmount,
            'discountAmount' => $this->discountAmount,
            'shippingFee' => $this->shippingFee,
            'totalAmount' => $this->totalAmount,
            'discountDetails' => array_map(fn (DiscountDetail $detail) => $detail->toArray(), $this->discountDetails),
            'currency' => $this->currency,
            'calculatedAt' => $this->calculatedAt?->format('Y-m-d H:i:s'),
            'message' => $this->message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getOriginalAmountAsFloat(): float
    {
        return (float) $this->originalAmount;
    }

    public function getProductAmountAsFloat(): float
    {
        return (float) $this->productAmount;
    }

    public function getDiscountAmountAsFloat(): float
    {
        return (float) $this->discountAmount;
    }

    public function getShippingFeeAsFloat(): float
    {
        return (float) $this->shippingFee;
    }

    public function getTotalAmountAsFloat(): float
    {
        return (float) $this->totalAmount;
    }

    public function hasDiscounts(): bool
    {
        return [] !== $this->discountDetails;
    }

    public function getDiscountCount(): int
    {
        return count($this->discountDetails);
    }

    public function isValid(): bool
    {
        return $this->success
            && $this->getTotalAmountAsFloat() >= 0
            && $this->getProductAmountAsFloat() >= 0
            && $this->getShippingFeeAsFloat() >= 0;
    }

    public function hasFreeShipping(): bool
    {
        return '0.00' === $this->shippingFee
               || [] !== array_filter($this->discountDetails, fn (DiscountDetail $detail) => $detail->isFreeFreight());
    }

    /**
     * @param array<DiscountDetail> $discountDetails
     */
    public static function success(
        string $originalAmount,
        string $productAmount,
        string $discountAmount,
        string $shippingFee,
        string $totalAmount,
        array $discountDetails = [],
        ?string $currency = 'CNY',
        ?string $message = null,
    ): self {
        return new self(
            originalAmount: $originalAmount,
            productAmount: $productAmount,
            discountAmount: $discountAmount,
            shippingFee: $shippingFee,
            totalAmount: $totalAmount,
            discountDetails: $discountDetails,
            success: true,
            message: $message,
            currency: $currency,
            calculatedAt: new \DateTimeImmutable()
        );
    }

    public static function failure(string $message, ?string $currency = 'CNY'): self
    {
        return new self(
            originalAmount: '0.00',
            productAmount: '0.00',
            discountAmount: '0.00',
            shippingFee: '0.00',
            totalAmount: '0.00',
            discountDetails: [],
            success: false,
            message: $message,
            currency: $currency,
            calculatedAt: new \DateTimeImmutable()
        );
    }
}
