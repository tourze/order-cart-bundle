<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

use RuntimeException;

final class CartOperationException extends \RuntimeException
{
    public static function unsupportedOperation(string $operation): self
    {
        return new self("不支持的操作类型: {$operation}");
    }

    public static function emptyOperation(): self
    {
        return new self('操作类型不能为空');
    }

    public static function invalidOperation(string $operation): self
    {
        return new self('无效的操作类型: ' . $operation);
    }

    public static function calculationFailed(string $reason): self
    {
        return new self('价格计算失败: ' . $reason);
    }
}
