<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\OrderCartBundle\Repository\CartAddLogRepository;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * 购物车加购记录表
 * 记录用户的所有加购行为，包括添加、更新、删除等操作
 */
#[ORM\Entity(repositoryClass: CartAddLogRepository::class)]
#[ORM\Table(name: 'cart_add_logs', options: ['comment' => '购物车加购记录表'])]
class CartAddLog implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?UserInterface $user = null;

    #[ORM\ManyToOne(targetEntity: Sku::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Sku $sku = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 36, nullable: true, options: ['comment' => '关联的购物车项ID'])]
    #[Assert\Length(max: 36)]
    private ?string $cartItemId = null;

    #[ORM\Column(options: ['comment' => '加购数量'])]
    #[Assert\Positive(message: '数量必须大于0')]
    #[Assert\LessThanOrEqual(value: 999, message: '数量不能超过999')]
    private int $quantity;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '商品快照信息'])]
    #[Assert\Type(type: 'array')]
    private array $skuSnapshot = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '价格快照'])]
    #[Assert\Type(type: 'array')]
    private array $priceSnapshot = [];

    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '购物车项是否已删除'])]
    #[Assert\Type(type: 'bool')]
    private bool $isDeleted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '购物车项删除时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeImmutable $deleteTime = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '操作类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['add', 'update', 'restore'], message: '操作类型必须是add、update或restore')]
    private string $action = 'add';

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '附加元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = [];

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user = null): void
    {
        $this->user = $user;
    }

    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    public function setSku(?Sku $sku = null): void
    {
        $this->sku = $sku;
    }

    public function getCartItemId(): ?string
    {
        return $this->cartItemId;
    }

    public function setCartItemId(?string $cartItemId): void
    {
        $this->cartItemId = $cartItemId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSkuSnapshot(): array
    {
        return $this->skuSnapshot;
    }

    /**
     * @param array<string, mixed> $skuSnapshot
     */
    public function setSkuSnapshot(array $skuSnapshot): void
    {
        $this->skuSnapshot = $skuSnapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPriceSnapshot(): array
    {
        return $this->priceSnapshot;
    }

    /**
     * @param array<string, mixed> $priceSnapshot
     */
    public function setPriceSnapshot(array $priceSnapshot): void
    {
        $this->priceSnapshot = $priceSnapshot;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function getIsDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getDeleteTime(): ?\DateTimeImmutable
    {
        return $this->deleteTime;
    }

    public function setDeleteTime(?\DateTimeImmutable $deleteTime): void
    {
        $this->deleteTime = $deleteTime;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * 标记为已删除
     */
    public function markAsDeleted(): self
    {
        $this->isDeleted = true;
        $this->deleteTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 恢复删除标记
     */
    public function unmarkDeleted(): self
    {
        $this->isDeleted = false;
        $this->deleteTime = null;
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('CartAddLog#%s (User: %s, SKU: %s, Action: %s, Quantity: %d)',
            $this->getId() ?? 'new',
            $this->user?->getUserIdentifier() ?? 'unknown',
            $this->sku?->getId() ?? 'unknown',
            $this->action,
            $this->quantity
        );
    }
}
