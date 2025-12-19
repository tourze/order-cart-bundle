<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

final class InvalidArgumentException extends CartException
{
    protected string $errorCode = 'INVALID_ARGUMENT';
}
