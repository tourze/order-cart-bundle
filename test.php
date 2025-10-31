<?php

declare(strict_types=1);

// 加载 Composer autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// 手动注册本包的命名空间
spl_autoload_register(function ($class): void {
    if (0 === strpos($class, 'Tourze\OrderCartBundle\\')) {
        $file = __DIR__ . '/src/' . str_replace(['Tourze\OrderCartBundle\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
    if (0 === strpos($class, 'Tourze\OrderCartBundle\Tests\\')) {
        $file = __DIR__ . '/tests/' . str_replace(['Tourze\OrderCartBundle\Tests\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// 运行测试
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\OrderCartBundle\OrderCartBundle;

try {
    echo "Testing OrderCartBundle instantiation...\n";
    $bundle = new OrderCartBundle();
    echo "✓ Bundle instantiated successfully\n";

    echo "Testing Bundle name...\n";
    if ('OrderCartBundle' === $bundle->getName()) {
        echo "✓ Bundle name is correct\n";
    } else {
        echo '✗ Bundle name is incorrect: ' . $bundle->getName() . "\n";
        exit(1);
    }

    echo "Testing Bundle path...\n";
    $path = $bundle->getPath();
    if (false !== strpos($path, 'order-cart-bundle') && is_dir($path)) {
        echo "✓ Bundle path is correct: {$path}\n";
    } else {
        echo "✗ Bundle path is incorrect: {$path}\n";
        exit(1);
    }

    echo "Testing container build...\n";
    $container = new ContainerBuilder();
    $bundle->build($container);
    echo "✓ Bundle can build container\n";

    echo "\n✅ All tests passed!\n";
    exit(0);
} catch (Exception $e) {
    echo '✗ Error: ' . $e->getMessage() . "\n";
    exit(1);
}
