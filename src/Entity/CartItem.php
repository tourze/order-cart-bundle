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
use Tourze\OrderCartBundle\Repository\CartItemRepository;
use Tourze\ProductCoreBundle\Entity\Sku;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_items', options: ['comment' => '购物车项目表'])]
#[ORM\UniqueConstraint(columns: ['user_id', 'sku_id'])]
class CartItem implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserInterface $user;

    #[ORM\ManyToOne(targetEntity: Sku::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Sku $sku;

    #[ORM\Column(options: ['comment' => '商品数量'])]
    #[Assert\Positive(message: '数量必须大于0')]
    #[Assert\LessThanOrEqual(value: 99, message: '单商品数量不能超过99个')]
    private int $quantity;

    #[ORM\Column(options: ['comment' => '是否选中'])]
    #[Assert\Type(type: 'bool')]
    private bool $selected = true;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '附加元数据'])]
    #[Assert\Type(type: 'array')]
    private array $metadata = [];

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getSku(): Sku
    {
        return $this->sku;
    }

    public function setSku(Sku $sku): void
    {
        $this->sku = $sku;
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

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function isChecked(): bool
    {
        return $this->selected;
    }

    public function setChecked(bool $checked): void
    {
        $this->setSelected($checked);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('CartItem#%s (User: %s, SKU: %s, Quantity: %d)',
            $this->getId() ?? 'new',
            $this->user->getUserIdentifier(),
            $this->sku->getId(),
            $this->quantity
        );
    }
}
