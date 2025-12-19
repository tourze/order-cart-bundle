<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\OrderCartBundle\Exception\CartValidationException;
use Tourze\OrderCartBundle\Param\AddToCartParam;
use Tourze\OrderCartBundle\Procedure\AddToCart;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

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
        $param = new AddToCartParam('0', 1);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithNegativeSkuIdShouldThrowException(): void
    {
        $param = new AddToCartParam('-1', 1);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithZeroQuantityShouldThrowException(): void
    {
        $param = new AddToCartParam('123', 0);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithNegativeQuantityShouldThrowException(): void
    {
        $param = new AddToCartParam('123', -1);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithExcessiveQuantityShouldThrowException(): void
    {
        $param = new AddToCartParam('123', 1000);

        $this->expectException(CartValidationException::class);

        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
    }

    public function testExecuteWithValidParametersShouldNotThrowException(): void
    {
        $param = new AddToCartParam('123', 5, ['color' => 'red']);

        // Should not throw exception
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithMaxValidQuantityShouldNotThrowException(): void
    {
        $param = new AddToCartParam('456', 999);

        // Should not throw exception
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithEmptyMetadataShouldNotThrowException(): void
    {
        $param = new AddToCartParam('789', 1, []);

        // Should not throw exception
        $reflection = new \ReflectionClass($this->procedure);
        $method = $reflection->getMethod('validateInput');
        $method->setAccessible(true);
        $method->invoke($this->procedure, $param);
        $this->expectNotToPerformAssertions();
    }

    public function testExecuteWithUnauthenticatedUserShouldReturnFailure(): void
    {
        // Ensure no user is logged in
        $security = self::getService(Security::class);
        $this->assertNull($security->getUser());

        $param = new AddToCartParam('123', 1);

        $result = $this->procedure->execute($param);

        $this->assertFalse($result['success']);
        // message字段已确定为字符串类型,无需重复检查
        $this->assertStringContainsString('操作失败:', $result['message']);
        $this->assertEquals(0, $result['affectedCount']);
    }

    public function testConstructorSetsUpDependenciesCorrectly(): void
    {
        // Test that the service can be instantiated correctly
        $procedure = self::getService(AddToCart::class);
        $this->assertInstanceOf(AddToCart::class, $procedure);
    }
}
