<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\DTO;

use Tourze\OrderCartBundle\Exception\InvalidArgumentException;

final readonly class CartSummaryDTO
{
    public function __construct(
        private int $totalItems,
        private int $selectedItems,
        private string $totalAmount,
        private string $selectedAmount,
    ) {
        if ($totalItems < 0) {
            throw new InvalidArgumentException('Total items cannot be negative');
        }
        if ($selectedItems < 0) {
            throw new InvalidArgumentException('Selected items cannot be negative');
        }
        if ($selectedItems > $totalItems) {
            throw new InvalidArgumentException('Selected items cannot exceed total items');
        }
        if (bccomp((string) (float) $totalAmount, '0.00', 2) < 0) {
            throw new InvalidArgumentException('Total amount cannot be negative');
        }
        if (bccomp((string) (float) $selectedAmount, '0.00', 2) < 0) {
            throw new InvalidArgumentException('Selected amount cannot be negative');
        }
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getSelectedItems(): int
    {
        return $this->selectedItems;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getSelectedAmount(): string
    {
        return $this->selectedAmount;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $totalItems = $data['totalItems'] ?? 0;
        $selectedItems = $data['selectedItems'] ?? 0;
        $totalAmount = $data['totalAmount'] ?? '0.00';
        $selectedAmount = $data['selectedAmount'] ?? '0.00';

        return new self(
            totalItems: \is_int($totalItems) ? $totalItems : 0,
            selectedItems: \is_int($selectedItems) ? $selectedItems : 0,
            totalAmount: \is_numeric($totalAmount) ? sprintf('%.2f', (float) $totalAmount) : '0.00',
            selectedAmount: \is_numeric($selectedAmount) ? sprintf('%.2f', (float) $selectedAmount) : '0.00'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'totalItems' => $this->totalItems,
            'selectedItems' => $this->selectedItems,
            'totalAmount' => $this->totalAmount,
            'selectedAmount' => $this->selectedAmount,
        ];
    }
}
