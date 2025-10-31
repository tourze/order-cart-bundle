<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Exception;

class CartItemNotFoundException extends CartException
{
    protected string $errorCode = 'CART_ITEM_NOT_FOUND';
}
