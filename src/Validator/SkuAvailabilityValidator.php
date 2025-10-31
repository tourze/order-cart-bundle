<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Validator;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Exception\InvalidSkuException;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductServiceContracts\SkuLoaderInterface;
use Tourze\StockManageBundle\Service\StockServiceInterface;

final readonly class SkuAvailabilityValidator implements CartItemValidatorInterface
{
    public function __construct(
        private SkuLoaderInterface $skuLoader,
        private StockServiceInterface $stockService,
    ) {
    }

    public function validate(UserInterface $user, Sku $sku, int $quantity): void
    {
        // Validate SKU exists and is valid
        $validSku = $this->skuLoader->loadSkuByIdentifier($sku->getId());
        if (null === $validSku) {
            throw new InvalidSkuException(sprintf('SKU %s is not valid', $sku->getId()));
        }

        // Check stock availability
        $stockSummary = $this->stockService->getAvailableStock($sku);
        if ($stockSummary->getAvailableQuantity() < $quantity) {
            throw new InvalidSkuException(sprintf('SKU %s is not available for quantity %d', $sku->getId(), $quantity));
        }
    }

    public function supports(Sku $sku): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        return 90;
    }
}
