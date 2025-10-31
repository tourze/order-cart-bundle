<?php

namespace Tourze\OrderCartBundle\DTO;

final readonly class BatchOperateResponse
{
    /**
     * @param array<string> $errors
     */
    public function __construct(
        public bool $success,
        public string $operation,
        public int $affectedCount,
        public int $totalCartItems,
        public int $totalQuantity,
        public ?string $message = null,
        public array $errors = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'operation' => $this->operation,
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

    public function getSummary(): string
    {
        if (!$this->success) {
            return sprintf('操作失败: %s', $this->message ?? '未知错误');
        }

        $operationMap = [
            'setChecked' => '批量勾选',
            'removeItems' => '批量删除',
            'checkAll' => '全选/取消全选',
        ];

        $operationName = $operationMap[$this->operation] ?? $this->operation;

        return sprintf(
            '%s操作成功，影响%d个项目，购物车总计%d个商品，总数量%d',
            $operationName,
            $this->affectedCount,
            $this->totalCartItems,
            $this->totalQuantity
        );
    }

    public static function success(
        string $operation,
        int $affectedCount,
        int $totalCartItems,
        int $totalQuantity,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            operation: $operation,
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
        string $operation,
        string $message,
        array $errors = [],
    ): self {
        return new self(
            success: false,
            operation: $operation,
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0,
            message: $message,
            errors: $errors
        );
    }
}
