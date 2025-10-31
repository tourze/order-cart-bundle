<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Procedure\AddToCart;

/**
 * @internal
 */
#[CoversClass(AddToCart::class)]
#[RunTestsInSeparateProcesses]
final class AddToCartTest extends AbstractProcedureTestCase
{
    private AddToCart $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(AddToCart::class);
    }

    public function testExecuteWithInvalidSkuIdShouldThrowException(): void
    {
        $this->procedure->skuId = '0';
        $this->procedure->quantity = 1;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithNegativeSkuIdShouldThrowException(): void
    {
        $this->procedure->skuId = '-1';
        $this->procedure->quantity = 1;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithZeroQuantityShouldThrowException(): void
    {
        $this->procedure->skuId = '123';
        $this->procedure->quantity = 0;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithNegativeQuantityShouldThrowException(): void
    {
        $this->procedure->skuId = '123';
        $this->procedure->quantity = -1;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithExcessiveQuantityShouldThrowException(): void
    {
        $this->procedure->skuId = '123';
        $this->procedure->quantity = 1000;

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
    }

    public function testExecuteWithValidParametersShouldNotThrowException(): void
    {
        $this->procedure->skuId = '123';
        $this->procedure->quantity = 5;
        $this->procedure->metadata = ['color' => 'red'];

        // Should not throw exception
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMaxValidQuantityShouldNotThrowException(): void
    {
        $this->procedure->skuId = '456';
        $this->procedure->quantity = 999;

        // Should not throw exception
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithEmptyMetadataShouldNotThrowException(): void
    {
        $this->procedure->skuId = '789';
        $this->procedure->quantity = 1;
        $this->procedure->metadata = [];

        // Should not throw exception
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithUnauthenticatedUserShouldReturnFailure(): void
    {
        // Ensure no user is logged in
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $this->procedure->skuId = '123';
        $this->procedure->quantity = 1;

        $result = $this->procedure->execute();

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertEquals(0, $result['affectedCount']);
    }

    public function testGetMockResultShouldReturnExpectedStructure(): void
    {
        $result = AddToCart::getMockResult();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('skuId', $result);
        $this->assertArrayHasKey('quantity', $result);
        $this->assertArrayHasKey('selected', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('createTime', $result);
        $this->assertArrayHasKey('updateTime', $result);
        $this->assertArrayHasKey('sku', $result);

        $this->assertIsString($result['id']);
        $this->assertIsInt($result['skuId']);
        $this->assertIsInt($result['quantity']);
        $this->assertIsBool($result['selected']);
        $this->assertIsArray($result['metadata']);
        $this->assertIsString($result['createTime']);
        $this->assertIsString($result['updateTime']);
        $this->assertIsArray($result['sku']);
    }

    public function testGetCurrentUserWithoutUserShouldFailAssertion(): void
    {
        // Test that getCurrentUser() fails when no user is authenticated
        $this->expectException(\AssertionError::class);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('getCurrentUser');
        $method->setAccessible(true);

        $method->invoke($this->procedure);
    }

    public function testConstructorSetsUpDependenciesCorrectly(): void
    {
        // Test that the service can be instantiated correctly
        $procedure = self::getService(AddToCart::class);
        $this->assertInstanceOf(AddToCart::class, $procedure);
    }

    public function testDefaultParameterValues(): void
    {
        $procedure = self::getService(AddToCart::class);

        $this->assertEquals(1, $procedure->quantity);
        $this->assertEquals([], $procedure->metadata);
    }
}
