<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class CalculateCartTotalParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '运费模板ID(可选)')]
        public ?string $freightId = null,

        #[MethodParam(description: '是否只计算已选中商品')]
        public bool $onlySelected = true,
    ) {
    }
}
