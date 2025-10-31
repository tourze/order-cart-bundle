<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class OrderCartExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
