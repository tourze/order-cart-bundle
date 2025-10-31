<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

class InvalidSkuException extends CartException
{
    protected string $errorCode = 'INVALID_SKU';
}
