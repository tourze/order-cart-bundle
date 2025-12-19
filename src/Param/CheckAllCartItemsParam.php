<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class CheckAllCartItemsParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '选中状态')]
        public bool $checked = false,
    ) {
    }
}
