<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Exception\InvalidQuantityException;
use Tourze\OrderCartBundle\Validator\QuantityValidator;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @internal
 */
#[CoversClass(QuantityValidator::class)]
final class QuantityValidatorTest extends TestCase
{
    private QuantityValidator $validator;

    private UserInterface $user;

    private Sku $sku;

    protected function setUp(): void
    {
        $this->validator = new QuantityValidator();
        $this->user = $this->createMock(UserInterface::class);
        $this->sku = $this->createMock(Sku::class);
    }

    public function testValidateValidQuantity(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->validate($this->user, $this->sku, 5);
    }

    public function testValidateZeroQuantityThrowsException(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Quantity must be at least 1');

        $this->validator->validate($this->user, $this->sku, 0);
    }

    public function testValidateNegativeQuantityThrowsException(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Quantity must be at least 1');

        $this->validator->validate($this->user, $this->sku, -1);
    }

    public function testValidateExcessiveQuantityThrowsException(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Quantity cannot exceed 999');

        $this->validator->validate($this->user, $this->sku, 1000);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->validator->supports($this->sku));
    }

    public function testGetPriority(): void
    {
        $this->assertSame(100, $this->validator->getPriority());
    }
}
