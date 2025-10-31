<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\OrderCartBundle\DependencyInjection\OrderCartExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * @internal
 */
#[CoversClass(OrderCartExtension::class)]
final class OrderCartExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private OrderCartExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new OrderCartExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testExtensionExtendsAutoExtension(): void
    {
        $this->assertInstanceOf(AutoExtension::class, $this->extension);
        $this->assertInstanceOf(Extension::class, $this->extension);
    }

    public function testConfigDirIsAccessible(): void
    {
        // 使用行为测试验证配置目录存在，而非反射调用私有方法
        $configVerifier = new class($this->extension) {
            public function __construct(private readonly OrderCartExtension $extension)
            {
            }

            public function verifyConfigPath(): ?string
            {
                // 通过扩展行为验证配置加载能力
                try {
                    // 如果配置目录不存在，load方法会失败
                    $container = new ContainerBuilder();
                    $container->setParameter('kernel.environment', 'test');
                    $this->extension->load([], $container);

                    // 如果能成功加载，说明配置路径正确
                    return 'config_load_successful';
                } catch (\Exception) {
                    return null;
                }
            }
        };

        $result = $configVerifier->verifyConfigPath();
        $this->assertEquals('config_load_successful', $result, 'Extension should be able to load configuration successfully');
    }

    public function testLoadWithEmptyConfigsShouldNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();
        $this->extension->load([], $this->container);
    }

    public function testLoadWithConfigurationShouldNotThrowException(): void
    {
        $configs = [
            'order_cart' => [],
        ];
        $this->expectNotToPerformAssertions();
        $this->extension->load($configs, $this->container);
    }

    public function testExtensionAliasShouldReturnCorrectValue(): void
    {
        $expectedAlias = 'order_cart';
        $actualAlias = $this->extension->getAlias();

        $this->assertEquals($expectedAlias, $actualAlias);
    }

    public function testLoadWithTestEnvironmentShouldNotThrowException(): void
    {
        $this->container->setParameter('kernel.environment', 'test');
        $this->expectNotToPerformAssertions();
        $this->extension->load([], $this->container);
    }

    public function testLoadWithDevEnvironmentShouldNotThrowException(): void
    {
        $this->container->setParameter('kernel.environment', 'dev');
        $this->expectNotToPerformAssertions();
        $this->extension->load([], $this->container);
    }

    public function testExtensionShouldNotFailWithMultipleLoads(): void
    {
        $this->expectNotToPerformAssertions();

        $this->extension->load([], $this->container);

        // 创建新的容器进行第二次加载
        $secondContainer = new ContainerBuilder();
        $secondContainer->setParameter('kernel.environment', 'test');
        $this->extension->load([], $secondContainer);
    }
}
