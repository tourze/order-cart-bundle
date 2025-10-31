<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Tourze\OrderCartBundle\DTO\BatchOperateRequest;

/**
 * @internal
 */
#[CoversClass(BatchOperateRequest::class)]
final class BatchOperateRequestTest extends TestCase
{
    private BatchOperateRequest $request;

    protected function setUp(): void
    {
        $this->request = new BatchOperateRequest();
    }

    public function testConstructorShouldSetDefaultValues(): void
    {
        $request = new BatchOperateRequest();

        $this->assertEquals('', $request->operation);
        $this->assertEquals([], $request->itemIds);
        $this->assertFalse($request->checked);
        $this->assertNull($request->userId);
    }

    public function testConstructorShouldSetCustomValues(): void
    {
        $itemIds = ['item1', 'item2', 'item3'];
        $request = new BatchOperateRequest(
            operation: 'setChecked',
            itemIds: $itemIds,
            checked: true,
            userId: 'user123'
        );

        $this->assertEquals('setChecked', $request->operation);
        $this->assertEquals($itemIds, $request->itemIds);
        $this->assertTrue($request->checked);
        $this->assertEquals('user123', $request->userId);
    }

    public function testToArrayShouldReturnAllProperties(): void
    {
        $this->request->operation = 'removeItems';
        $this->request->itemIds = ['item1', 'item2'];
        $this->request->checked = true;
        $this->request->userId = 'user456';

        $result = $this->request->toArray();

        $expected = [
            'operation' => 'removeItems',
            'itemIds' => ['item1', 'item2'],
            'checked' => true,
            'userId' => 'user456',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testIsSetCheckedOperationShouldReturnCorrectValue(): void
    {
        $this->request->operation = 'setChecked';
        $this->assertTrue($this->request->isSetCheckedOperation());

        $this->request->operation = 'removeItems';
        $this->assertFalse($this->request->isSetCheckedOperation());

        $this->request->operation = 'checkAll';
        $this->assertFalse($this->request->isSetCheckedOperation());
    }

    public function testIsRemoveItemsOperationShouldReturnCorrectValue(): void
    {
        $this->request->operation = 'removeItems';
        $this->assertTrue($this->request->isRemoveItemsOperation());

        $this->request->operation = 'setChecked';
        $this->assertFalse($this->request->isRemoveItemsOperation());

        $this->request->operation = 'checkAll';
        $this->assertFalse($this->request->isRemoveItemsOperation());
    }

    public function testIsCheckAllOperationShouldReturnCorrectValue(): void
    {
        $this->request->operation = 'checkAll';
        $this->assertTrue($this->request->isCheckAllOperation());

        $this->request->operation = 'setChecked';
        $this->assertFalse($this->request->isCheckAllOperation());

        $this->request->operation = 'removeItems';
        $this->assertFalse($this->request->isCheckAllOperation());
    }

    public function testHasItemIdsShouldReturnCorrectValue(): void
    {
        $this->request->itemIds = [];
        $this->assertFalse($this->request->hasItemIds());

        $this->request->itemIds = ['item1'];
        $this->assertTrue($this->request->hasItemIds());

        $this->request->itemIds = ['item1', 'item2', 'item3'];
        $this->assertTrue($this->request->hasItemIds());
    }

    public function testGetItemCountShouldReturnCorrectCount(): void
    {
        $this->request->itemIds = [];
        $this->assertEquals(0, $this->request->getItemCount());

        $this->request->itemIds = ['item1'];
        $this->assertEquals(1, $this->request->getItemCount());

        $this->request->itemIds = ['item1', 'item2', 'item3'];
        $this->assertEquals(3, $this->request->getItemCount());
    }

    public function testValidOperationsShouldPassValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $validOperations = ['setChecked', 'removeItems', 'checkAll'];

        foreach ($validOperations as $operation) {
            $this->request->operation = $operation;
            $violations = $validator->validateProperty($this->request, 'operation');

            $this->assertCount(0, $violations, "操作 '{$operation}' 应通过验证");
        }
    }

    public function testInvalidOperationShouldFailValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->operation = 'invalidOperation';
        $violations = $validator->validateProperty($this->request, 'operation');

        $this->assertCount(1, $violations, '无效操作应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('操作类型无效', $firstViolation->getMessage());
    }

    public function testEmptyOperationShouldFailValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->operation = '';
        $violations = $validator->validateProperty($this->request, 'operation');

        $this->assertCount(2, $violations, '空操作应产生2个验证错误（NotBlank和Choice约束）');

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        $this->assertContains('操作类型不能为空', $violationMessages);
        $this->assertContains('操作类型无效', $violationMessages);
    }

    public function testValidItemIdsShouldPassValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->itemIds = ['item1', 'item2', 'item3'];
        $violations = $validator->validateProperty($this->request, 'itemIds');

        $this->assertCount(0, $violations, '有效的项目ID数组应通过验证');
    }

    public function testTooManyItemIdsShouldFailValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->itemIds = array_fill(0, 201, 'item_id');
        $violations = $validator->validateProperty($this->request, 'itemIds');

        $this->assertCount(1, $violations, '超过200个项目ID应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('一次最多操作200个项目', $firstViolation->getMessage());
    }

    public function testEmptyItemIdsShouldFailValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->itemIds = ['item1', '', 'item3'];
        $violations = $validator->validateProperty($this->request, 'itemIds');

        $this->assertCount(1, $violations, '包含空字符串的项目ID应产生验证错误');
        $firstViolation = $violations[0] ?? null;
        $this->assertNotNull($firstViolation, 'Should have at least one violation');
        $this->assertEquals('项目ID不能为空', $firstViolation->getMessage());
    }

    public function testCheckedPropertyShouldAcceptBooleanValues(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->checked = true;
        $violations = $validator->validateProperty($this->request, 'checked');
        $this->assertCount(0, $violations, 'checked=true应通过验证');

        $this->request->checked = false;
        $violations = $validator->validateProperty($this->request, 'checked');
        $this->assertCount(0, $violations, 'checked=false应通过验证');
    }

    public function testUserIdPropertyShouldAcceptStringValues(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->userId = 'user123';
        $violations = $validator->validateProperty($this->request, 'userId');
        $this->assertCount(0, $violations, '字符串用户ID应通过验证');

        $this->request->userId = null;
        $violations = $validator->validateProperty($this->request, 'userId');
        $this->assertCount(0, $violations, 'null用户ID应通过验证');
    }

    public function testCompleteRequestShouldPassValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;

        $this->request->operation = 'setChecked';
        $this->request->itemIds = ['item1', 'item2', 'item3'];
        $this->request->checked = true;
        $this->request->userId = 'user123';

        $violations = $validator->validate($this->request);

        $this->assertCount(0, $violations, '完整的有效请求应通过验证');
    }
}
