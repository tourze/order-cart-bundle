<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class ToggleCartItemSelectionParam implements RpcParamInterface
{
    public function __construct(
        /**
         * @var array<string>|string
         */
        #[MethodParam(description: '购物车商品ID,单个或多个')]
        public string|array $cartItemIds = '',

        #[MethodParam(description: '是否选中')]
        public bool $selected = true,
    ) {
    }
}
