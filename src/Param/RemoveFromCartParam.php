<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class RemoveFromCartParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '购物车商品ID')]
        public string $cartItemId = '',
    ) {
    }
}
