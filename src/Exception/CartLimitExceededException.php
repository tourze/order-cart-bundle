<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

final class CartLimitExceededException extends CartException
{
    protected string $errorCode = 'CART_LIMIT_EXCEEDED';
}
