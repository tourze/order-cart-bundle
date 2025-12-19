<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class AddToCartParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: 'SKU ID')]
        public string $skuId,

        #[MethodParam(description: '商品数量')]
        public int $quantity = 1,

        /**
         * @var array<string, mixed>
         */
        #[MethodParam(description: '商品元数据')]
        public array $metadata = [],
    ) {
    }
}
