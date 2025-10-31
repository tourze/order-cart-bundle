<?php

namespace Tourze\OrderCartBundle\DTO;

final readonly class CartOperationResponse
{
    /**
     * @param array<string> $errors
     */
    public function __construct(
        public bool $success,
        public int $affectedCount,
        public int $totalCartItems,
        public int $totalQuantity,
        public ?string $message = null,
        public array $errors = [],
    ) {
    }

    public static function success(
        int $affectedCount,
        int $totalCartItems,
        int $totalQuantity,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            affectedCount: $affectedCount,
            totalCartItems: $totalCartItems,
            totalQuantity: $totalQuantity,
            message: $message
        );
    }

    /**
     * @param array<string> $errors
     */
    public static function failure(
        string $message,
        array $errors = [],
    ): self {
        return new self(
            success: false,
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0,
            message: $message,
            errors: $errors
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'affectedCount' => $this->affectedCount,
            'totalCartItems' => $this->totalCartItems,
            'totalQuantity' => $this->totalQuantity,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }
}
