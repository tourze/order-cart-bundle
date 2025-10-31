<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validation;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(CartItem::class)]
final class CartItemTest extends AbstractEntityTestCase
{
    private CartItem $cartItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartItem = new CartItem();
    }

    protected function createEntity(): object
    {
        return new CartItem();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'quantity' => ['quantity', 5];
        yield 'selected' => ['selected', false];
        yield 'metadata' => ['metadata', ['color' => 'red', 'size' => 'XL']];
    }

    public function testConstructorShouldSetDefaultValues(): void
    {
        $cartItem = new CartItem();

        $this->assertTrue($cartItem->isSelected(), '构造函数应设置selected默认值为true');
        $this->assertEquals([], $cartItem->getMetadata(), '构造函数应设置metadata默认值为空数组');
        $this->assertInstanceOf(\DateTimeInterface::class, $cartItem->getCreateTime(), '构造函数应设置创建时间');
        $this->assertInstanceOf(\DateTimeInterface::class, $cartItem->getUpdateTime(), '构造函数应设置更新时间');
    }

    public function testSetUserShouldReturnSelfForChaining(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser@example.com');

        $this->cartItem->setUser($user);
        $this->assertSame($user, $this->cartItem->getUser(), 'getUser应返回设置的用户');
    }

    public function testSetSkuShouldReturnSelfForChaining(): void
    {
        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('SKU123456789');

        $this->cartItem->setSku($sku);
        $this->assertSame($sku, $this->cartItem->getSku(), 'getSku应返回设置的SKU');
    }

    public function testSetQuantityShouldUpdateQuantityAndUpdatedAt(): void
    {
        $originalUpdateTime = $this->cartItem->getUpdateTime();
        usleep(1000);

        $this->cartItem->setQuantity(10);
        $this->assertEquals(10, $this->cartItem->getQuantity(), 'getQuantity应返回设置的数量');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartItem->getUpdateTime(),
            '设置数量应更新updatedAt时间'
        );
    }

    public function testSetSelectedShouldUpdateSelectedAndUpdatedAt(): void
    {
        $originalUpdateTime = $this->cartItem->getUpdateTime();
        usleep(1000);

        $this->cartItem->setSelected(false);
        $this->assertFalse($this->cartItem->isSelected(), 'isSelected应返回设置的选中状态');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartItem->getUpdateTime(),
            '设置选中状态应更新updatedAt时间'
        );
    }

    public function testSetMetadataShouldUpdateMetadataAndUpdatedAt(): void
    {
        $originalUpdateTime = $this->cartItem->getUpdateTime();
        usleep(1000);
        $metadata = ['customAttribute' => 'customValue', 'priority' => 'high'];

        $this->cartItem->setMetadata($metadata);
        $this->assertEquals($metadata, $this->cartItem->getMetadata(), 'getMetadata应返回设置的元数据');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartItem->getUpdateTime(),
            '设置元数据应更新updatedAt时间'
        );
    }

    public function testSetCreatedAtShouldSetCreateTime(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-15 10:30:00');

        $this->cartItem->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->cartItem->getCreateTime(), 'getCreateTime应返回设置的创建时间');
    }

    public function testSetUpdatedAtShouldSetUpdateTime(): void
    {
        $dateTime = new \DateTimeImmutable('2023-01-15 15:45:00');

        $this->cartItem->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->cartItem->getUpdateTime(), 'getUpdateTime应返回设置的更新时间');
    }

    public function testToStringShouldReturnFormattedStringWithNewEntity(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser@example.com');

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('SKU123456789');

        $this->cartItem->setUser($user);
        $this->cartItem->setSku($sku);
        $this->cartItem->setQuantity(3);

        $result = $this->cartItem->__toString();

        $this->assertStringContainsString('CartItem#new', $result, '__toString应显示新实体标识');
        $this->assertStringContainsString('testuser@example.com', $result, '__toString应包含用户标识');
        $this->assertStringContainsString('SKU123456789', $result, '__toString应包含SKU ID');
        $this->assertStringContainsString('Quantity: 3', $result, '__toString应包含数量信息');
    }

    public function testToStringShouldHandleUserWithEmptyIdentifier(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('');

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('SKU987654321');

        $this->cartItem->setUser($user);
        $this->cartItem->setSku($sku);
        $this->cartItem->setQuantity(2);

        $result = $this->cartItem->__toString();

        $this->assertStringContainsString('User: ', $result, '__toString应处理返回空字符串的getUserIdentifier方法');
        $this->assertStringContainsString('SKU987654321', $result, '__toString应包含SKU ID');
        $this->assertStringContainsString('Quantity: 2', $result, '__toString应包含数量信息');
    }

    public function testToStringShouldHandleSkuWithEmptyId(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser@example.com');

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('');

        $this->cartItem->setUser($user);
        $this->cartItem->setSku($sku);
        $this->cartItem->setQuantity(1);

        $result = $this->cartItem->__toString();

        $this->assertStringContainsString('SKU: ', $result, '__toString应处理空ID的SKU');
        $this->assertStringContainsString('testuser@example.com', $result, '__toString应包含用户标识');
        $this->assertStringContainsString('Quantity: 1', $result, '__toString应包含数量信息');
    }

    public function testQuantityValidationConstraintShouldRequirePositiveNumber(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartItem->setQuantity(0);

        $violations = $validator->validateProperty($this->cartItem, 'quantity');

        $this->assertCount(1, $violations, '数量为0应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('数量必须大于0', $firstViolation->getMessage(), '验证错误消息应匹配约束定义');
    }

    public function testQuantityValidationConstraintShouldAllowPositiveNumbers(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartItem->setQuantity(5);

        $violations = $validator->validateProperty($this->cartItem, 'quantity');

        $this->assertCount(0, $violations, '正数数量应通过验证');
    }

    public function testQuantityValidationConstraintShouldRejectNegativeNumbers(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartItem->setQuantity(-1);

        $violations = $validator->validateProperty($this->cartItem, 'quantity');

        $this->assertCount(1, $violations, '负数数量应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('数量必须大于0', $firstViolation->getMessage(), '验证错误消息应匹配约束定义');
    }

    public function testQuantityValidationConstraintShouldRejectNumbersAboveMaximum(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartItem->setQuantity(100);

        $violations = $validator->validateProperty($this->cartItem, 'quantity');

        $this->assertCount(1, $violations, '超过99的数量应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('单商品数量不能超过99个', $firstViolation->getMessage(), '验证错误消息应匹配约束定义');
    }

    public function testQuantityValidationConstraintShouldAllowMaximumQuantity(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartItem->setQuantity(99);

        $violations = $validator->validateProperty($this->cartItem, 'quantity');

        $this->assertCount(0, $violations, '99个数量应通过验证');
    }

    public function testMetadataShouldHandleEmptyArray(): void
    {
        $this->cartItem->setMetadata([]);

        $this->assertEquals([], $this->cartItem->getMetadata(), '应能设置空的元数据数组');
    }

    public function testMetadataShouldHandleComplexArray(): void
    {
        $complexMetadata = [
            'attributes' => [
                'color' => 'blue',
                'size' => 'L',
                'material' => 'cotton',
            ],
            'preferences' => [
                'gift_wrap' => true,
                'express_delivery' => false,
            ],
            'notes' => '客户备注信息',
        ];

        $this->cartItem->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $this->cartItem->getMetadata(), '应能处理复杂的嵌套元数据');
    }

    public function testSelectedPropertyShouldHaveCorrectDefaultValue(): void
    {
        $cartItem = new CartItem();

        $this->assertTrue($cartItem->isSelected(), '新创建的购物车项目应默认选中');
    }

    public function testDateTimePropertiesShouldBeSetInConstructor(): void
    {
        $beforeCreate = new \DateTime();
        $cartItem = new CartItem();
        $afterCreate = new \DateTime();

        $this->assertGreaterThanOrEqual($beforeCreate, $cartItem->getCreateTime(), '创建时间应大于等于实例化前时间');
        $this->assertLessThanOrEqual($afterCreate, $cartItem->getCreateTime(), '创建时间应小于等于实例化后时间');

        $this->assertGreaterThanOrEqual($beforeCreate, $cartItem->getUpdateTime(), '更新时间应大于等于实例化前时间');
        $this->assertLessThanOrEqual($afterCreate, $cartItem->getUpdateTime(), '更新时间应小于等于实例化后时间');
    }

    public function testDateTimeImmutableCompatibility(): void
    {
        $immutableDateTime = new \DateTimeImmutable('2023-06-15 12:00:00');

        $this->cartItem->setCreateTime($immutableDateTime);
        $this->cartItem->setUpdateTime($immutableDateTime);

        $this->assertSame($immutableDateTime, $this->cartItem->getCreateTime(), '应支持DateTimeImmutable作为创建时间');
        $this->assertSame($immutableDateTime, $this->cartItem->getUpdateTime(), '应支持DateTimeImmutable作为更新时间');
    }

    public function testCheckedAliasMethods(): void
    {
        $this->assertTrue($this->cartItem->isChecked(), '默认情况下isChecked应返回true');

        $this->cartItem->setChecked(false);
        $this->assertFalse($this->cartItem->isChecked(), 'setChecked(false)后isChecked应返回false');
        $this->assertFalse($this->cartItem->isSelected(), 'setChecked应同步更新selected字段');

        $this->cartItem->setChecked(true);

        $this->assertTrue($this->cartItem->isChecked(), 'setChecked(true)后isChecked应返回true');
        $this->assertTrue($this->cartItem->isSelected(), 'setChecked应同步更新selected字段');
    }

    public function testCheckedAliasMethodsShouldUpdateTimestamp(): void
    {
        $originalUpdatedAt = $this->cartItem->getUpdateTime();
        usleep(1000);

        $this->cartItem->setChecked(false);

        $this->assertGreaterThan($originalUpdatedAt, $this->cartItem->getUpdateTime(), 'setChecked应更新updatedAt时间');
    }
}
