<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\OrderCartBundle\Validator\SkuAvailabilityValidator;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductServiceContracts\SkuLoaderInterface;
use Tourze\StockManageBundle\Model\StockSummary;
use Tourze\StockManageBundle\Service\StockServiceInterface;

/**
 * @internal
 */
#[CoversClass(SkuAvailabilityValidator::class)]
final class SkuAvailabilityValidatorTest extends TestCase
{
    private SkuAvailabilityValidator $validator;

    private MockObject&SkuLoaderInterface $skuLoader;

    private MockObject&StockServiceInterface $stockService;

    private UserInterface $user;

    private Sku $sku;

    protected function setUp(): void
    {
        $this->skuLoader = $this->createMock(SkuLoaderInterface::class);
        $this->stockService = $this->createMock(StockServiceInterface::class);
        $this->validator = new SkuAvailabilityValidator($this->skuLoader, $this->stockService);
        $this->user = $this->createMock(UserInterface::class);
        $this->sku = $this->createMock(Sku::class);
        $this->sku->method('getId')->willReturn('123');
    }

    public function testValidateValidSku(): void
    {
        $this->skuLoader->expects($this->once())
            ->method('loadSkuByIdentifier')
            ->with('123')
            ->willReturn($this->sku)
        ;

        $stockSummary = $this->createMock(StockSummary::class);
        $stockSummary->method('getAvailableQuantity')->willReturn(10);

        $this->stockService->expects($this->once())
            ->method('getAvailableStock')
            ->with($this->sku)
            ->willReturn($stockSummary)
        ;

        $this->validator->validate($this->user, $this->sku, 5);

        // 验证方法调用已通过expects()断言
    }

    public function testValidateInvalidSkuThrowsException(): void
    {
        $this->expectException(InvalidSkuException::class);
        $this->expectExceptionMessage('SKU 123 is not valid');

        $this->skuLoader->method('loadSkuByIdentifier')->with('123')->willReturn(null);

        $this->validator->validate($this->user, $this->sku, 5);
    }

    public function testValidateUnavailableSkuThrowsException(): void
    {
        $this->expectException(InvalidSkuException::class);
        $this->expectExceptionMessage('SKU 123 is not available for quantity 5');

        $this->skuLoader->method('loadSkuByIdentifier')->with('123')->willReturn($this->sku);

        $stockSummary = $this->createMock(StockSummary::class);
        $stockSummary->method('getAvailableQuantity')->willReturn(3); // Less than required

        $this->stockService->method('getAvailableStock')->with($this->sku)->willReturn($stockSummary);

        $this->validator->validate($this->user, $this->sku, 5);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->validator->supports($this->sku));
    }

    public function testGetPriority(): void
    {
        $this->assertSame(90, $this->validator->getPriority());
    }
}
