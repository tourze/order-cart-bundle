<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\DataFixtures;

use BizUserBundle\Entity\BizUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

class CartItemFixtures extends Fixture
{
    public const CART_ITEM_REFERENCE = 'cart-item';

    public function load(ObjectManager $manager): void
    {
        // 尝试使用引用获取用户和SKU数据
        $users = $this->getUsersFromReferences();
        $skus = $this->getSkusFromReferences();

        if ([] === $users || [] === $skus) {
            // 没有外部依赖数据时创建基本测试数据
            $this->createBasicTestData($manager);

            return;
        }

        $cartItems = [];

        // 为每个用户创建一些购物车项目，确保每个用户使用不同的SKU
        foreach ($users as $userIndex => $user) {
            // 每个用户使用不同的SKU子集，避免重复的user_id+sku_id组合
            $startIndex = $userIndex % count($skus);
            $userSkus = array_slice($skus, $startIndex, min(2, count($skus))); // 每个用户最多2个商品

            foreach ($userSkus as $skuIndex => $sku) {
                $cartItem = new CartItem();
                $cartItem->setUser($user);
                $cartItem->setSku($sku);
                $cartItem->setQuantity($skuIndex + 1); // 数量从1开始
                $cartItem->setSelected($skuIndex < 1); // 第一个商品默认选中
                $cartItem->setMetadata([
                    'source' => 'fixtures',
                    'created_by' => 'system',
                ]);

                $manager->persist($cartItem);
                $cartItems[] = $cartItem;
            }
        }

        $manager->flush();

        // 设置引用以供其他fixtures使用
        foreach ($cartItems as $index => $cartItem) {
            $this->addReference("cart_item_{$index}", $cartItem);
        }
    }

    /**
     * @return UserInterface[]
     */
    private function getUsersFromReferences(): array
    {
        $users = [];

        // 尝试获取用户引用
        $userReferences = ['user_1', 'user_2', 'admin_user', 'test_user'];

        foreach ($userReferences as $reference) {
            try {
                if ($this->hasReference($reference, BizUser::class)) {
                    $users[] = $this->getReference($reference, BizUser::class);
                }
            } catch (\Exception $e) {
                // 引用不存在，继续尝试下一个
                continue;
            }
        }

        return $users;
    }

    /**
     * @return Sku[]
     */
    private function getSkusFromReferences(): array
    {
        $skus = [];

        // 尝试获取SKU引用
        for ($i = 1; $i <= 10; ++$i) {
            try {
                $reference = "sku_{$i}";
                if ($this->hasReference($reference, Sku::class)) {
                    $skus[] = $this->getReference($reference, Sku::class);
                }
            } catch (\Exception $e) {
                // 引用不存在，继续尝试下一个
                continue;
            }
        }

        return $skus;
    }

    private function createBasicTestData(ObjectManager $manager): void
    {
        // 创建基本的测试用户
        $testUser = new BizUser();
        $testUser->setUsername('fixtures_user_' . uniqid());
        $testUser->setEmail('fixtures@localhost.test');
        $testUser->setPasswordHash('$2y$13$test_hash');
        $manager->persist($testUser);

        // 创建基本的测试SPU和SKU
        $testSpu = new Spu();
        $testSpu->setTitle('Fixtures Test SPU ' . uniqid());
        $manager->persist($testSpu);

        $testSku = new Sku();
        $testSku->setSpu($testSpu);

        // 使用反射设置unit属性
        $reflection = new \ReflectionClass($testSku);
        $unitProperty = $reflection->getProperty('unit');
        $unitProperty->setAccessible(true);
        $unitProperty->setValue($testSku, '个');

        $manager->persist($testSku);

        // 创建基本的CartItem
        $cartItem = new CartItem();
        $cartItem->setUser($testUser);
        $cartItem->setSku($testSku);
        $cartItem->setQuantity(1);
        $cartItem->setSelected(true);
        $cartItem->setMetadata(['source' => 'basic_fixtures']);

        $manager->persist($cartItem);
        $manager->flush();

        // 设置引用
        $this->addReference(self::CART_ITEM_REFERENCE, $cartItem);
    }
}
