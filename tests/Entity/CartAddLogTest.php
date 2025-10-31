<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validation;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(CartAddLog::class)]
final class CartAddLogTest extends AbstractEntityTestCase
{
    private CartAddLog $cartAddLog;

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'cartItemId' => ['cartItemId', 'cart_item_123456'];
        yield 'quantity' => ['quantity', 5];
        yield 'skuSnapshot' => ['skuSnapshot', ['id' => 'sku_123', 'title' => 'Test SKU']];
        yield 'priceSnapshot' => ['priceSnapshot', ['prices' => [['type' => 'sale', 'price' => 9999]]]];
        yield 'isDeleted' => ['isDeleted', true];
        yield 'action' => ['action', 'update'];
        yield 'metadata' => ['metadata', ['source' => 'api', 'notes' => 'test']];
    }

    public function testConstructorShouldSetDefaultValues(): void
    {
        $cartAddLog = new CartAddLog();

        $this->assertFalse($cartAddLog->isDeleted(), '构造函数应设置isDeleted默认值为false');
        $this->assertNull($cartAddLog->getDeleteTime(), '构造函数应设置deleteTime默认值为null');
        $this->assertEquals('add', $cartAddLog->getAction(), '构造函数应设置action默认值为add');
        $this->assertEquals([], $cartAddLog->getSkuSnapshot(), '构造函数应设置skuSnapshot默认值为空数组');
        $this->assertEquals([], $cartAddLog->getPriceSnapshot(), '构造函数应设置priceSnapshot默认值为空数组');
        $this->assertEquals([], $cartAddLog->getMetadata(), '构造函数应设置metadata默认值为空数组');
        $this->assertInstanceOf(\DateTimeInterface::class, $cartAddLog->getCreateTime(), '构造函数应设置创建时间');
        $this->assertInstanceOf(\DateTimeInterface::class, $cartAddLog->getUpdateTime(), '构造函数应设置更新时间');
    }

    public function testSetUserShouldSetUserCorrectly(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser@example.com');

        $this->cartAddLog->setUser($user);

        $this->assertSame($user, $this->cartAddLog->getUser(), 'getUser应返回设置的用户');
    }

    public function testSetSkuShouldSetSkuCorrectly(): void
    {
        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('SKU123456789');

        $this->cartAddLog->setSku($sku);

        $this->assertSame($sku, $this->cartAddLog->getSku(), 'getSku应返回设置的SKU');
    }

    public function testSetCartItemIdShouldSetCartItemIdCorrectly(): void
    {
        $cartItemId = 'cart_item_123456';

        $this->cartAddLog->setCartItemId($cartItemId);

        $this->assertEquals($cartItemId, $this->cartAddLog->getCartItemId(), 'getCartItemId应返回设置的购物车项ID');
    }

    public function testSetQuantityShouldUpdateQuantityAndUpdatedAt(): void
    {
        $originalUpdateTime = $this->cartAddLog->getUpdateTime();
        usleep(1000);

        $this->cartAddLog->setQuantity(10);
        $this->assertEquals(10, $this->cartAddLog->getQuantity(), 'getQuantity应返回设置的数量');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartAddLog->getUpdateTime(),
            '设置数量应更新updateTime时间'
        );
    }

    public function testSetSkuSnapshotShouldReturnSelfForChaining(): void
    {
        $skuSnapshot = [
            'id' => 'sku_123',
            'title' => 'Test SKU',
            'unit' => '个',
            'valid' => true,
        ];

        $this->cartAddLog->setSkuSnapshot($skuSnapshot);
        $this->assertEquals($skuSnapshot, $this->cartAddLog->getSkuSnapshot(), 'getSkuSnapshot应返回设置的SKU快照');
    }

    public function testSetPriceSnapshotShouldReturnSelfForChaining(): void
    {
        $priceSnapshot = [
            'prices' => [
                [
                    'type' => 'sale',
                    'price' => 9999,
                    'currency' => 'CNY',
                ],
            ],
            'snapshot_time' => '2023-01-15 12:00:00',
        ];

        $this->cartAddLog->setPriceSnapshot($priceSnapshot);
        $this->assertEquals($priceSnapshot, $this->cartAddLog->getPriceSnapshot(), 'getPriceSnapshot应返回设置的价格快照');
    }

    public function testSetIsDeletedShouldUpdateIsDeletedAndUpdatedAt(): void
    {
        $originalUpdateTime = $this->cartAddLog->getUpdateTime();
        usleep(1000);

        $this->cartAddLog->setIsDeleted(true);
        $this->assertTrue($this->cartAddLog->isDeleted(), 'isDeleted应返回设置的删除状态');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartAddLog->getUpdateTime(),
            '设置删除状态应更新updateTime时间'
        );
    }

    public function testSetDeleteTimeShouldUpdateDeleteTimeAndUpdatedAt(): void
    {
        $deleteTime = new \DateTimeImmutable('2023-01-15 10:30:00');
        $originalUpdateTime = $this->cartAddLog->getUpdateTime();
        usleep(1000);

        $this->cartAddLog->setDeleteTime($deleteTime);
        $this->assertSame($deleteTime, $this->cartAddLog->getDeleteTime(), 'getDeleteTime应返回设置的删除时间');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartAddLog->getUpdateTime(),
            '设置删除时间应更新updateTime时间'
        );
    }

    public function testSetActionShouldReturnSelfForChaining(): void
    {
        $this->cartAddLog->setAction('update');
        $this->assertEquals('update', $this->cartAddLog->getAction(), 'getAction应返回设置的操作类型');
    }

    public function testSetMetadataShouldUpdateMetadataAndUpdatedAt(): void
    {
        $metadata = [
            'source' => 'api',
            'notes' => 'user request',
            'priority' => 'high',
        ];
        $originalUpdateTime = $this->cartAddLog->getUpdateTime();
        usleep(1000);

        $this->cartAddLog->setMetadata($metadata);
        $this->assertEquals($metadata, $this->cartAddLog->getMetadata(), 'getMetadata应返回设置的元数据');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartAddLog->getUpdateTime(),
            '设置元数据应更新updateTime时间'
        );
    }

    public function testMarkAsDeletedShouldSetDeletedFlagsAndUpdateTime(): void
    {
        $originalUpdateTime = $this->cartAddLog->getUpdateTime();
        usleep(1000);

        $result = $this->cartAddLog->markAsDeleted();

        $this->assertSame($this->cartAddLog, $result, 'markAsDeleted应返回自身实例用于链式调用');
        $this->assertTrue($this->cartAddLog->isDeleted(), 'markAsDeleted应设置isDeleted为true');
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->cartAddLog->getDeleteTime(), 'markAsDeleted应设置deleteTime');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartAddLog->getUpdateTime(),
            'markAsDeleted应更新updateTime时间'
        );
    }

    public function testUnmarkDeletedShouldResetDeletedFlagsAndUpdateTime(): void
    {
        // 先标记为删除
        $this->cartAddLog->markAsDeleted();
        $originalUpdateTime = $this->cartAddLog->getUpdateTime();
        usleep(1000);

        $result = $this->cartAddLog->unmarkDeleted();

        $this->assertSame($this->cartAddLog, $result, 'unmarkDeleted应返回自身实例用于链式调用');
        $this->assertFalse($this->cartAddLog->isDeleted(), 'unmarkDeleted应设置isDeleted为false');
        $this->assertNull($this->cartAddLog->getDeleteTime(), 'unmarkDeleted应设置deleteTime为null');
        $this->assertGreaterThan(
            $originalUpdateTime,
            $this->cartAddLog->getUpdateTime(),
            'unmarkDeleted应更新updateTime时间'
        );
    }

    public function testToStringShouldReturnFormattedStringWithNewEntity(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser@example.com');

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('SKU123456789');

        $this->cartAddLog->setUser($user);
        $this->cartAddLog->setSku($sku);
        $this->cartAddLog->setAction('add');
        $this->cartAddLog->setQuantity(3);

        $result = $this->cartAddLog->__toString();

        $this->assertStringContainsString('CartAddLog#new', $result, '__toString应显示新实体标识');
        $this->assertStringContainsString('testuser@example.com', $result, '__toString应包含用户标识');
        $this->assertStringContainsString('SKU123456789', $result, '__toString应包含SKU ID');
        $this->assertStringContainsString('Action: add', $result, '__toString应包含操作类型');
        $this->assertStringContainsString('Quantity: 3', $result, '__toString应包含数量信息');
    }

    public function testToStringShouldHandleUserWithEmptyIdentifier(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('');

        $sku = $this->createMock(Sku::class);
        $sku->method('getId')->willReturn('SKU987654321');

        $this->cartAddLog->setUser($user);
        $this->cartAddLog->setSku($sku);
        $this->cartAddLog->setAction('update');
        $this->cartAddLog->setQuantity(2);

        $result = $this->cartAddLog->__toString();

        $this->assertStringContainsString('User: ', $result, '__toString应处理返回空字符串的getUserIdentifier方法');
        $this->assertStringContainsString('SKU987654321', $result, '__toString应包含SKU ID');
        $this->assertStringContainsString('Action: update', $result, '__toString应包含操作类型');
        $this->assertStringContainsString('Quantity: 2', $result, '__toString应包含数量信息');
    }

    public function testQuantityValidationConstraintShouldRequirePositiveNumber(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartAddLog->setQuantity(0);

        $violations = $validator->validateProperty($this->cartAddLog, 'quantity');

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

        $this->cartAddLog->setQuantity(5);

        $violations = $validator->validateProperty($this->cartAddLog, 'quantity');

        $this->assertCount(0, $violations, '正数数量应通过验证');
    }

    public function testQuantityValidationConstraintShouldRejectNegativeNumbers(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartAddLog->setQuantity(-1);

        $violations = $validator->validateProperty($this->cartAddLog, 'quantity');

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

        $this->cartAddLog->setQuantity(1000);

        $violations = $validator->validateProperty($this->cartAddLog, 'quantity');

        $this->assertCount(1, $violations, '超过999的数量应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('数量不能超过999', $firstViolation->getMessage(), '验证错误消息应匹配约束定义');
    }

    public function testQuantityValidationConstraintShouldAllowMaximumQuantity(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartAddLog->setQuantity(999);

        $violations = $validator->validateProperty($this->cartAddLog, 'quantity');

        $this->assertCount(0, $violations, '999个数量应通过验证');
    }

    public function testActionValidationConstraintShouldAllowValidActions(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $validActions = ['add', 'update', 'restore'];

        foreach ($validActions as $action) {
            $this->cartAddLog->setAction($action);
            $violations = $validator->validateProperty($this->cartAddLog, 'action');
            $this->assertCount(0, $violations, "操作类型'{$action}'应通过验证");
        }
    }

    public function testActionValidationConstraintShouldRejectInvalidActions(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->cartAddLog->setAction('invalid_action');

        $violations = $validator->validateProperty($this->cartAddLog, 'action');

        $this->assertCount(1, $violations, '无效的操作类型应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('操作类型必须是add、update或restore', $firstViolation->getMessage(), '验证错误消息应匹配约束定义');
    }

    public function testCartItemIdLengthValidationShouldRejectTooLongIds(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $longId = str_repeat('a', 37); // 37 characters, exceeds max length of 36
        $this->cartAddLog->setCartItemId($longId);

        $violations = $validator->validateProperty($this->cartAddLog, 'cartItemId');

        $this->assertGreaterThan(0, $violations->count(), '超过36字符的购物车项ID应产生验证错误');
    }

    public function testCartItemIdLengthValidationShouldAllowValidLength(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $validId = str_repeat('a', 36); // 36 characters, exactly at max length
        $this->cartAddLog->setCartItemId($validId);

        $violations = $validator->validateProperty($this->cartAddLog, 'cartItemId');

        $this->assertCount(0, $violations, '36字符或以下的购物车项ID应通过验证');
    }

    public function testMetadataShouldHandleEmptyArray(): void
    {
        $this->cartAddLog->setMetadata([]);

        $this->assertEquals([], $this->cartAddLog->getMetadata(), '应能设置空的元数据数组');
    }

    public function testMetadataShouldHandleComplexArray(): void
    {
        $complexMetadata = [
            'source' => 'mobile_app',
            'device' => [
                'type' => 'ios',
                'version' => '15.0',
            ],
            'location' => [
                'country' => 'CN',
                'city' => 'Shanghai',
            ],
            'timestamp' => time(),
        ];

        $this->cartAddLog->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $this->cartAddLog->getMetadata(), '应能处理复杂的嵌套元数据');
    }

    public function testDateTimePropertiesShouldBeSetInConstructor(): void
    {
        $beforeCreate = new \DateTime();
        $cartAddLog = new CartAddLog();
        $afterCreate = new \DateTime();

        $this->assertGreaterThanOrEqual($beforeCreate, $cartAddLog->getCreateTime(), '创建时间应大于等于实例化前时间');
        $this->assertLessThanOrEqual($afterCreate, $cartAddLog->getCreateTime(), '创建时间应小于等于实例化后时间');

        $this->assertGreaterThanOrEqual($beforeCreate, $cartAddLog->getUpdateTime(), '更新时间应大于等于实例化前时间');
        $this->assertLessThanOrEqual($afterCreate, $cartAddLog->getUpdateTime(), '更新时间应小于等于实例化后时间');
    }

    public function testSkuSnapshotShouldHandleEmptyArray(): void
    {
        $this->cartAddLog->setSkuSnapshot([]);

        $this->assertEquals([], $this->cartAddLog->getSkuSnapshot(), '应能设置空的SKU快照数组');
    }

    public function testPriceSnapshotShouldHandleEmptyArray(): void
    {
        $this->cartAddLog->setPriceSnapshot([]);

        $this->assertEquals([], $this->cartAddLog->getPriceSnapshot(), '应能设置空的价格快照数组');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartAddLog = new CartAddLog();
    }

    protected function createEntity(): object
    {
        return new CartAddLog();
    }
}
