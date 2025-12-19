<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class RemoveCartItemsParam implements RpcParamInterface
{
    public function __construct(
        /**
         * @var array<string>
         */
        #[MethodParam(description: '项目ID列表(最多200个)')]
        public array $itemIds = [],
    ) {
    }
}
