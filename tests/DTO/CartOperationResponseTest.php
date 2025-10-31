<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\CartOperationResponse;

/**
 * @internal
 */
#[CoversClass(CartOperationResponse::class)]
final class CartOperationResponseTest extends TestCase
{
    public function testSuccessResponseShouldReturnCorrectValues(): void
    {
        $response = CartOperationResponse::success(5, 10, 25, 'Success message');

        self::assertTrue($response->success);
        self::assertSame(5, $response->affectedCount);
        self::assertSame(10, $response->totalCartItems);
        self::assertSame(25, $response->totalQuantity);
        self::assertSame('Success message', $response->message);
        self::assertSame([], $response->errors);
    }

    public function testFailureResponseShouldReturnCorrectValues(): void
    {
        $errors = ['error1', 'error2'];
        $response = CartOperationResponse::failure('Error message', $errors);

        self::assertFalse($response->success);
        self::assertSame(0, $response->affectedCount);
        self::assertSame(0, $response->totalCartItems);
        self::assertSame(0, $response->totalQuantity);
        self::assertSame('Error message', $response->message);
        self::assertSame($errors, $response->errors);
    }

    public function testToArrayShouldReturnAllProperties(): void
    {
        $response = CartOperationResponse::success(3, 8, 15, 'Test message');
        $expected = [
            'success' => true,
            'affectedCount' => 3,
            'totalCartItems' => 8,
            'totalQuantity' => 15,
            'message' => 'Test message',
            'errors' => [],
        ];

        self::assertSame($expected, $response->toArray());
    }

    public function testIsSuccessShouldReturnCorrectValue(): void
    {
        $successResponse = CartOperationResponse::success(1, 5, 10);
        $failureResponse = CartOperationResponse::failure('Error');

        self::assertTrue($successResponse->isSuccess());
        self::assertFalse($failureResponse->isSuccess());
    }

    public function testHasErrorsShouldReturnCorrectValue(): void
    {
        $noErrorsResponse = CartOperationResponse::success(1, 5, 10);
        $withErrorsResponse = CartOperationResponse::failure('Error', ['error1']);

        self::assertFalse($noErrorsResponse->hasErrors());
        self::assertTrue($withErrorsResponse->hasErrors());
    }

    public function testGetErrorCountShouldReturnCorrectValue(): void
    {
        $noErrorsResponse = CartOperationResponse::success(1, 5, 10);
        $withErrorsResponse = CartOperationResponse::failure('Error', ['error1', 'error2']);

        self::assertSame(0, $noErrorsResponse->getErrorCount());
        self::assertSame(2, $withErrorsResponse->getErrorCount());
    }

    public function testSuccessWithNullMessageShouldWork(): void
    {
        $response = CartOperationResponse::success(1, 5, 10, null);

        self::assertNull($response->message);
    }

    public function testFailureWithEmptyErrorsShouldWork(): void
    {
        $response = CartOperationResponse::failure('Error message');

        self::assertSame([], $response->errors);
        self::assertFalse($response->hasErrors());
        self::assertSame(0, $response->getErrorCount());
    }
}
