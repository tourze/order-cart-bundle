<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

abstract class CartException extends \Exception
{
    protected string $errorCode = 'CART_ERROR';

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
