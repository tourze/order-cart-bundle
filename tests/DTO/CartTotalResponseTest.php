<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\CartTotalResponse;
use Tourze\OrderCartBundle\DTO\DiscountDetail;

/**
 * @internal
 */
#[CoversClass(CartTotalResponse::class)]
final class CartTotalResponseTest extends TestCase
{
    public function testConstructorShouldSetAllProperties(): void
    {
        $discountDetails = [
            new DiscountDetail('reduction', '满500减50', '50.00'),
            new DiscountDetail('free-freight', '满99免邮', '0.00'),
        ];

        $calculatedAt = new \DateTimeImmutable('2023-08-15 10:30:00');

        $response = new CartTotalResponse(
            originalAmount: '500.00',
            productAmount: '450.00',
            discountAmount: '50.00',
            shippingFee: '0.00',
            totalAmount: '450.00',
            discountDetails: $discountDetails,
            success: true,
            message: '计算成功',
            currency: 'CNY',
            calculatedAt: $calculatedAt
        );

        $this->assertEquals('500.00', $response->originalAmount);
        $this->assertEquals('450.00', $response->productAmount);
        $this->assertEquals('50.00', $response->discountAmount);
        $this->assertEquals('0.00', $response->shippingFee);
        $this->assertEquals('450.00', $response->totalAmount);
        $this->assertEquals($discountDetails, $response->discountDetails);
        $this->assertTrue($response->success);
        $this->assertEquals('计算成功', $response->message);
        $this->assertEquals('CNY', $response->currency);
        $this->assertEquals($calculatedAt, $response->calculatedAt);
    }

    public function testConstructorWithDefaultValuesShouldSetCorrectDefaults(): void
    {
        $response = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '100.00',
            discountAmount: '0.00',
            shippingFee: '10.00',
            totalAmount: '110.00'
        );

        $this->assertEquals([], $response->discountDetails);
        $this->assertTrue($response->success);
        $this->assertNull($response->message);
        $this->assertEquals('CNY', $response->currency);
        $this->assertNull($response->calculatedAt);
    }

    public function testToArrayShouldReturnAllProperties(): void
    {
        $discountDetails = [
            new DiscountDetail('reduction', '立减优惠', '20.00'),
        ];

        $calculatedAt = new \DateTimeImmutable('2023-08-15 14:30:00');

        $response = new CartTotalResponse(
            originalAmount: '200.00',
            productAmount: '180.00',
            discountAmount: '20.00',
            shippingFee: '15.00',
            totalAmount: '195.00',
            discountDetails: $discountDetails,
            success: true,
            message: '测试消息',
            currency: 'USD',
            calculatedAt: $calculatedAt
        );

        $result = $response->toArray();

        $expected = [
            'success' => true,
            'originalAmount' => '200.00',
            'productAmount' => '180.00',
            'discountAmount' => '20.00',
            'shippingFee' => '15.00',
            'totalAmount' => '195.00',
            'discountDetails' => [
                [
                    'type' => 'reduction',
                    'name' => '立减优惠',
                    'amount' => '20.00',
                    'description' => null,
                ],
            ],
            'currency' => 'USD',
            'calculatedAt' => '2023-08-15 14:30:00',
            'message' => '测试消息',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testJsonSerializeShouldReturnSameAsToArray(): void
    {
        $response = new CartTotalResponse('100.00', '100.00', '0.00', '10.00', '110.00');

        $this->assertEquals($response->toArray(), $response->jsonSerialize());
    }

    public function testAmountAsFloatMethodsShouldReturnCorrectValues(): void
    {
        $response = new CartTotalResponse(
            originalAmount: '123.45',
            productAmount: '98.76',
            discountAmount: '24.69',
            shippingFee: '12.34',
            totalAmount: '111.10'
        );

        $this->assertEquals(123.45, $response->getOriginalAmountAsFloat());
        $this->assertEquals(98.76, $response->getProductAmountAsFloat());
        $this->assertEquals(24.69, $response->getDiscountAmountAsFloat());
        $this->assertEquals(12.34, $response->getShippingFeeAsFloat());
        $this->assertEquals(111.10, $response->getTotalAmountAsFloat());
    }

    public function testHasDiscountsShouldReturnCorrectValue(): void
    {
        $responseWithDiscounts = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '90.00',
            discountAmount: '10.00',
            shippingFee: '5.00',
            totalAmount: '95.00',
            discountDetails: [new DiscountDetail('reduction', '测试', '10.00')]
        );

        $responseWithoutDiscounts = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '100.00',
            discountAmount: '0.00',
            shippingFee: '5.00',
            totalAmount: '105.00'
        );

        $this->assertTrue($responseWithDiscounts->hasDiscounts());
        $this->assertFalse($responseWithoutDiscounts->hasDiscounts());
    }

    public function testGetDiscountCountShouldReturnCorrectCount(): void
    {
        $multipleDiscounts = new CartTotalResponse(
            originalAmount: '200.00',
            productAmount: '150.00',
            discountAmount: '50.00',
            shippingFee: '0.00',
            totalAmount: '150.00',
            discountDetails: [
                new DiscountDetail('reduction', '立减1', '30.00'),
                new DiscountDetail('discount', '打折', '20.00'),
                new DiscountDetail('free-freight', '免邮', '0.00'),
            ]
        );

        $noDiscounts = new CartTotalResponse('100.00', '100.00', '0.00', '10.00', '110.00');

        $this->assertEquals(3, $multipleDiscounts->getDiscountCount());
        $this->assertEquals(0, $noDiscounts->getDiscountCount());
    }

    public function testIsValidShouldReturnCorrectValue(): void
    {
        $validResponse = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '90.00',
            discountAmount: '10.00',
            shippingFee: '5.00',
            totalAmount: '95.00',
            success: true
        );

        $invalidSuccessResponse = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '90.00',
            discountAmount: '10.00',
            shippingFee: '5.00',
            totalAmount: '95.00',
            success: false
        );

        $invalidNegativeResponse = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '-10.00',
            discountAmount: '10.00',
            shippingFee: '5.00',
            totalAmount: '-5.00',
            success: true
        );

        $this->assertTrue($validResponse->isValid());
        $this->assertFalse($invalidSuccessResponse->isValid());
        $this->assertFalse($invalidNegativeResponse->isValid());
    }

    public function testHasFreeShippingShouldReturnCorrectValue(): void
    {
        $freeShippingByAmount = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '100.00',
            discountAmount: '0.00',
            shippingFee: '0.00',
            totalAmount: '100.00'
        );

        $freeShippingByDiscount = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '100.00',
            discountAmount: '0.00',
            shippingFee: '10.00',
            totalAmount: '110.00',
            discountDetails: [
                new DiscountDetail('free-freight', '免邮优惠', '0.00'),
            ]
        );

        $notFreeShipping = new CartTotalResponse(
            originalAmount: '100.00',
            productAmount: '100.00',
            discountAmount: '0.00',
            shippingFee: '10.00',
            totalAmount: '110.00'
        );

        $this->assertTrue($freeShippingByAmount->hasFreeShipping());
        $this->assertTrue($freeShippingByDiscount->hasFreeShipping());
        $this->assertFalse($notFreeShipping->hasFreeShipping());
    }

    public function testSuccessFactoryMethodShouldCreateCorrectResponse(): void
    {
        $discountDetails = [
            new DiscountDetail('reduction', '立减优惠', '25.00'),
        ];

        $response = CartTotalResponse::success(
            originalAmount: '300.00',
            productAmount: '275.00',
            discountAmount: '25.00',
            shippingFee: '12.00',
            totalAmount: '287.00',
            discountDetails: $discountDetails,
            currency: 'USD',
            message: '计算成功'
        );

        $this->assertTrue($response->success);
        $this->assertEquals('300.00', $response->originalAmount);
        $this->assertEquals('275.00', $response->productAmount);
        $this->assertEquals('25.00', $response->discountAmount);
        $this->assertEquals('12.00', $response->shippingFee);
        $this->assertEquals('287.00', $response->totalAmount);
        $this->assertEquals($discountDetails, $response->discountDetails);
        $this->assertEquals('USD', $response->currency);
        $this->assertEquals('计算成功', $response->message);
        $this->assertInstanceOf(\DateTimeImmutable::class, $response->calculatedAt);
    }

    public function testSuccessFactoryMethodWithDefaultsShouldCreateCorrectResponse(): void
    {
        $response = CartTotalResponse::success(
            originalAmount: '150.00',
            productAmount: '150.00',
            discountAmount: '0.00',
            shippingFee: '8.00',
            totalAmount: '158.00'
        );

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->discountDetails);
        $this->assertEquals('CNY', $response->currency);
        $this->assertNull($response->message);
        $this->assertInstanceOf(\DateTimeImmutable::class, $response->calculatedAt);
    }

    public function testFailureFactoryMethodShouldCreateCorrectResponse(): void
    {
        $response = CartTotalResponse::failure('计算失败', 'EUR');

        $this->assertFalse($response->success);
        $this->assertEquals('0.00', $response->originalAmount);
        $this->assertEquals('0.00', $response->productAmount);
        $this->assertEquals('0.00', $response->discountAmount);
        $this->assertEquals('0.00', $response->shippingFee);
        $this->assertEquals('0.00', $response->totalAmount);
        $this->assertEquals([], $response->discountDetails);
        $this->assertEquals('EUR', $response->currency);
        $this->assertEquals('计算失败', $response->message);
        $this->assertInstanceOf(\DateTimeImmutable::class, $response->calculatedAt);
    }

    public function testFailureFactoryMethodWithDefaultCurrencyShouldCreateCorrectResponse(): void
    {
        $response = CartTotalResponse::failure('系统错误');

        $this->assertFalse($response->success);
        $this->assertEquals('CNY', $response->currency);
        $this->assertEquals('系统错误', $response->message);
    }
}
