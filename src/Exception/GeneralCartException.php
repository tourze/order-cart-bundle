<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

final class GeneralCartException extends CartException
{
    protected string $errorCode = 'GENERAL_CART_ERROR';
}
