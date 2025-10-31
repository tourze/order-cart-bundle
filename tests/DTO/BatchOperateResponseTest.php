<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderCartBundle\DTO\BatchOperateResponse;

/**
 * @internal
 */
#[CoversClass(BatchOperateResponse::class)]
final class BatchOperateResponseTest extends TestCase
{
    public function testConstructorShouldSetAllProperties(): void
    {
        $response = new BatchOperateResponse(
            success: true,
            operation: 'setChecked',
            affectedCount: 5,
            totalCartItems: 10,
            totalQuantity: 25,
            message: '操作成功',
            errors: []
        );

        $this->assertTrue($response->success);
        $this->assertEquals('setChecked', $response->operation);
        $this->assertEquals(5, $response->affectedCount);
        $this->assertEquals(10, $response->totalCartItems);
        $this->assertEquals(25, $response->totalQuantity);
        $this->assertEquals('操作成功', $response->message);
        $this->assertEquals([], $response->errors);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $response = new BatchOperateResponse(
            success: false,
            operation: 'removeItems',
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0
        );

        $this->assertFalse($response->success);
        $this->assertEquals('removeItems', $response->operation);
        $this->assertEquals(0, $response->affectedCount);
        $this->assertEquals(0, $response->totalCartItems);
        $this->assertEquals(0, $response->totalQuantity);
        $this->assertNull($response->message);
        $this->assertEquals([], $response->errors);
    }

    public function testToArrayShouldReturnAllProperties(): void
    {
        $response = new BatchOperateResponse(
            success: true,
            operation: 'checkAll',
            affectedCount: 3,
            totalCartItems: 8,
            totalQuantity: 20,
            message: '全选成功',
            errors: ['error1', 'error2']
        );

        $result = $response->toArray();

        $expected = [
            'success' => true,
            'operation' => 'checkAll',
            'affectedCount' => 3,
            'totalCartItems' => 8,
            'totalQuantity' => 20,
            'message' => '全选成功',
            'errors' => ['error1', 'error2'],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testIsSuccessShouldReturnCorrectValue(): void
    {
        $successResponse = new BatchOperateResponse(
            success: true,
            operation: 'setChecked',
            affectedCount: 1,
            totalCartItems: 1,
            totalQuantity: 1
        );

        $failureResponse = new BatchOperateResponse(
            success: false,
            operation: 'setChecked',
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0
        );

        $this->assertTrue($successResponse->isSuccess());
        $this->assertFalse($failureResponse->isSuccess());
    }

    public function testHasErrorsShouldReturnCorrectValue(): void
    {
        $responseWithErrors = new BatchOperateResponse(
            success: false,
            operation: 'setChecked',
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0,
            errors: ['error1', 'error2']
        );

        $responseWithoutErrors = new BatchOperateResponse(
            success: true,
            operation: 'setChecked',
            affectedCount: 1,
            totalCartItems: 1,
            totalQuantity: 1,
            errors: []
        );

        $this->assertTrue($responseWithErrors->hasErrors());
        $this->assertFalse($responseWithoutErrors->hasErrors());
    }

    public function testGetErrorCountShouldReturnCorrectCount(): void
    {
        $responseWithErrors = new BatchOperateResponse(
            success: false,
            operation: 'setChecked',
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0,
            errors: ['error1', 'error2', 'error3']
        );

        $responseWithoutErrors = new BatchOperateResponse(
            success: true,
            operation: 'setChecked',
            affectedCount: 1,
            totalCartItems: 1,
            totalQuantity: 1
        );

        $this->assertEquals(3, $responseWithErrors->getErrorCount());
        $this->assertEquals(0, $responseWithoutErrors->getErrorCount());
    }

    public function testGetSummaryShouldReturnCorrectFailureMessage(): void
    {
        $failureResponse = new BatchOperateResponse(
            success: false,
            operation: 'setChecked',
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0,
            message: '数据库连接失败'
        );

        $summary = $failureResponse->getSummary();

        $this->assertEquals('操作失败: 数据库连接失败', $summary);
    }

    public function testGetSummaryShouldReturnCorrectFailureMessageWithoutMessage(): void
    {
        $failureResponse = new BatchOperateResponse(
            success: false,
            operation: 'setChecked',
            affectedCount: 0,
            totalCartItems: 0,
            totalQuantity: 0
        );

        $summary = $failureResponse->getSummary();

        $this->assertEquals('操作失败: 未知错误', $summary);
    }

    public function testGetSummaryShouldReturnCorrectSuccessMessageForSetChecked(): void
    {
        $successResponse = new BatchOperateResponse(
            success: true,
            operation: 'setChecked',
            affectedCount: 3,
            totalCartItems: 10,
            totalQuantity: 25
        );

        $summary = $successResponse->getSummary();

        $this->assertEquals('批量勾选操作成功，影响3个项目，购物车总计10个商品，总数量25', $summary);
    }

    public function testGetSummaryShouldReturnCorrectSuccessMessageForRemoveItems(): void
    {
        $successResponse = new BatchOperateResponse(
            success: true,
            operation: 'removeItems',
            affectedCount: 2,
            totalCartItems: 8,
            totalQuantity: 20
        );

        $summary = $successResponse->getSummary();

        $this->assertEquals('批量删除操作成功，影响2个项目，购物车总计8个商品，总数量20', $summary);
    }

    public function testGetSummaryShouldReturnCorrectSuccessMessageForCheckAll(): void
    {
        $successResponse = new BatchOperateResponse(
            success: true,
            operation: 'checkAll',
            affectedCount: 5,
            totalCartItems: 5,
            totalQuantity: 15
        );

        $summary = $successResponse->getSummary();

        $this->assertEquals('全选/取消全选操作成功，影响5个项目，购物车总计5个商品，总数量15', $summary);
    }

    public function testGetSummaryShouldReturnCorrectSuccessMessageForUnknownOperation(): void
    {
        $successResponse = new BatchOperateResponse(
            success: true,
            operation: 'unknownOperation',
            affectedCount: 1,
            totalCartItems: 1,
            totalQuantity: 1
        );

        $summary = $successResponse->getSummary();

        $this->assertEquals('unknownOperation操作成功，影响1个项目，购物车总计1个商品，总数量1', $summary);
    }

    public function testSuccessFactoryMethodShouldCreateCorrectResponse(): void
    {
        $response = BatchOperateResponse::success(
            operation: 'setChecked',
            affectedCount: 3,
            totalCartItems: 10,
            totalQuantity: 25,
            message: '勾选成功'
        );

        $this->assertTrue($response->success);
        $this->assertEquals('setChecked', $response->operation);
        $this->assertEquals(3, $response->affectedCount);
        $this->assertEquals(10, $response->totalCartItems);
        $this->assertEquals(25, $response->totalQuantity);
        $this->assertEquals('勾选成功', $response->message);
        $this->assertEquals([], $response->errors);
    }

    public function testSuccessFactoryMethodWithoutMessageShouldCreateCorrectResponse(): void
    {
        $response = BatchOperateResponse::success(
            operation: 'removeItems',
            affectedCount: 2,
            totalCartItems: 8,
            totalQuantity: 20
        );

        $this->assertTrue($response->success);
        $this->assertEquals('removeItems', $response->operation);
        $this->assertEquals(2, $response->affectedCount);
        $this->assertEquals(8, $response->totalCartItems);
        $this->assertEquals(20, $response->totalQuantity);
        $this->assertNull($response->message);
        $this->assertEquals([], $response->errors);
    }

    public function testFailureFactoryMethodShouldCreateCorrectResponse(): void
    {
        $errors = ['错误1', '错误2'];
        $response = BatchOperateResponse::failure(
            operation: 'setChecked',
            message: '操作失败',
            errors: $errors
        );

        $this->assertFalse($response->success);
        $this->assertEquals('setChecked', $response->operation);
        $this->assertEquals(0, $response->affectedCount);
        $this->assertEquals(0, $response->totalCartItems);
        $this->assertEquals(0, $response->totalQuantity);
        $this->assertEquals('操作失败', $response->message);
        $this->assertEquals($errors, $response->errors);
    }

    public function testFailureFactoryMethodWithoutErrorsShouldCreateCorrectResponse(): void
    {
        $response = BatchOperateResponse::failure(
            operation: 'checkAll',
            message: '用户未登录'
        );

        $this->assertFalse($response->success);
        $this->assertEquals('checkAll', $response->operation);
        $this->assertEquals(0, $response->affectedCount);
        $this->assertEquals(0, $response->totalCartItems);
        $this->assertEquals(0, $response->totalQuantity);
        $this->assertEquals('用户未登录', $response->message);
        $this->assertEquals([], $response->errors);
    }
}
