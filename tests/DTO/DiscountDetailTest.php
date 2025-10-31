<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\DiscountDetail;

/**
 * @internal
 */
#[CoversClass(DiscountDetail::class)]
final class DiscountDetailTest extends TestCase
{
    public function testConstructorShouldSetAllProperties(): void
    {
        $detail = new DiscountDetail(
            type: 'reduction',
            name: '满500减50',
            amount: '50.00',
            description: '满500元立减50元'
        );

        $this->assertEquals('reduction', $detail->type);
        $this->assertEquals('满500减50', $detail->name);
        $this->assertEquals('50.00', $detail->amount);
        $this->assertEquals('满500元立减50元', $detail->description);
    }

    public function testConstructorWithoutDescriptionShouldSetDefaultValue(): void
    {
        $detail = new DiscountDetail(
            type: 'discount',
            name: '全场9折',
            amount: '20.00'
        );

        $this->assertEquals('discount', $detail->type);
        $this->assertEquals('全场9折', $detail->name);
        $this->assertEquals('20.00', $detail->amount);
        $this->assertNull($detail->description);
    }

    public function testToArrayShouldReturnAllProperties(): void
    {
        $detail = new DiscountDetail(
            type: 'free-freight',
            name: '满99免邮',
            amount: '0.00',
            description: '满99元包邮'
        );

        $result = $detail->toArray();

        $expected = [
            'type' => 'free-freight',
            'name' => '满99免邮',
            'amount' => '0.00',
            'description' => '满99元包邮',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testJsonSerializeShouldReturnSameAsToArray(): void
    {
        $detail = new DiscountDetail(
            type: 'coupon',
            name: '新用户优惠券',
            amount: '30.00'
        );

        $this->assertEquals($detail->toArray(), $detail->jsonSerialize());
    }

    public function testGetAmountAsFloatShouldReturnCorrectValue(): void
    {
        $detail = new DiscountDetail('reduction', '测试', '123.45');

        $this->assertEquals(123.45, $detail->getAmountAsFloat());
    }

    public function testGetAmountAsFloatWithZeroShouldReturnZero(): void
    {
        $detail = new DiscountDetail('free-freight', '免邮', '0.00');

        $this->assertEquals(0.0, $detail->getAmountAsFloat());
    }

    public function testIsReductionShouldReturnCorrectValue(): void
    {
        $reductionDetail = new DiscountDetail('reduction', '立减', '50.00');
        $discountDetail = new DiscountDetail('discount', '打折', '20.00');

        $this->assertTrue($reductionDetail->isReduction());
        $this->assertFalse($discountDetail->isReduction());
    }

    public function testIsDiscountShouldReturnCorrectValue(): void
    {
        $discountDetail = new DiscountDetail('discount', '打折', '20.00');
        $reductionDetail = new DiscountDetail('reduction', '立减', '50.00');

        $this->assertTrue($discountDetail->isDiscount());
        $this->assertFalse($reductionDetail->isDiscount());
    }

    public function testIsFreeFreightShouldReturnCorrectValue(): void
    {
        $freeFreightDetail = new DiscountDetail('free-freight', '免邮', '0.00');
        $reductionDetail = new DiscountDetail('reduction', '立减', '50.00');

        $this->assertTrue($freeFreightDetail->isFreeFreight());
        $this->assertFalse($reductionDetail->isFreeFreight());
    }

    public function testIsCouponShouldReturnCorrectValue(): void
    {
        $couponDetail = new DiscountDetail('coupon', '优惠券', '30.00');
        $reductionDetail = new DiscountDetail('reduction', '立减', '50.00');

        $this->assertTrue($couponDetail->isCoupon());
        $this->assertFalse($reductionDetail->isCoupon());
    }

    public function testAllDiscountTypesCanBeIdentified(): void
    {
        $types = [
            'reduction' => new DiscountDetail('reduction', 'test', '10.00'),
            'discount' => new DiscountDetail('discount', 'test', '10.00'),
            'free-freight' => new DiscountDetail('free-freight', 'test', '10.00'),
            'coupon' => new DiscountDetail('coupon', 'test', '10.00'),
        ];

        $this->assertTrue($types['reduction']->isReduction());
        $this->assertTrue($types['discount']->isDiscount());
        $this->assertTrue($types['free-freight']->isFreeFreight());
        $this->assertTrue($types['coupon']->isCoupon());

        // 确保类型互斥
        $this->assertFalse($types['reduction']->isDiscount());
        $this->assertFalse($types['discount']->isReduction());
        $this->assertFalse($types['free-freight']->isCoupon());
        $this->assertFalse($types['coupon']->isFreeFreight());
    }
}
