<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

final class InvalidQuantityException extends CartException
{
    protected string $errorCode = 'INVALID_QUANTITY';
}
