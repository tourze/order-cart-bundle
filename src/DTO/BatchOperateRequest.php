<?php

namespace Tourze\OrderCartBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class BatchOperateRequest
{
    #[Assert\NotBlank(message: '操作类型不能为空')]
    #[Assert\Choice(choices: ['setChecked', 'removeItems', 'checkAll'], message: '操作类型无效')]
    public string $operation;

    /**
     * @var array<string>
     */
    #[Assert\Type(type: 'array', message: '项目ID列表必须为数组')]
    #[Assert\Count(max: 200, maxMessage: '一次最多操作200个项目')]
    #[Assert\All(constraints: [
        new Assert\NotBlank(message: '项目ID不能为空'),
        new Assert\Type(type: 'string', message: '项目ID必须为字符串'),
    ])]
    public array $itemIds = [];

    #[Assert\Type(type: 'bool', message: '选中状态必须为布尔值')]
    public bool $checked = false;

    #[Assert\Type(type: 'string', message: '用户ID必须为字符串')]
    public ?string $userId = null;

    /**
     * @param array<string> $itemIds
     */
    public function __construct(
        string $operation = '',
        array $itemIds = [],
        bool $checked = false,
        ?string $userId = null,
    ) {
        $this->operation = $operation;
        $this->itemIds = $itemIds;
        $this->checked = $checked;
        $this->userId = $userId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'itemIds' => $this->itemIds,
            'checked' => $this->checked,
            'userId' => $this->userId,
        ];
    }

    public function isSetCheckedOperation(): bool
    {
        return 'setChecked' === $this->operation;
    }

    public function isRemoveItemsOperation(): bool
    {
        return 'removeItems' === $this->operation;
    }

    public function isCheckAllOperation(): bool
    {
        return 'checkAll' === $this->operation;
    }

    public function hasItemIds(): bool
    {
        return [] !== $this->itemIds;
    }

    public function getItemCount(): int
    {
        return count($this->itemIds);
    }
}
