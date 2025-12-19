<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class GetCartListParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '是否只获取已选中的商品')]
        public bool $selectedOnly = false,
    ) {
    }
}
