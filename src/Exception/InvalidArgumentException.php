<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

class InvalidArgumentException extends CartException
{
    protected string $errorCode = 'INVALID_ARGUMENT';
}
