<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class UpdateCartQuantityParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '购物车商品ID')]
        public string $cartItemId = '',

        #[MethodParam(description: '新数量')]
        public int $quantity = 1,
    ) {
    }
}
