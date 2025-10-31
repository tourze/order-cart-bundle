<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\DataFixtures;

use BizUserBundle\Entity\BizUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

class CartAddLogFixtures extends Fixture
{
    public const CART_ADD_LOG_REFERENCE = 'cart-add-log';

    public function load(ObjectManager $manager): void
    {
        // 尝试使用引用获取用户、SKU和购物车项目数据
        $users = $this->getUsersFromReferences();
        $skus = $this->getSkusFromReferences();
        $cartItems = $this->getCartItemsFromReferences();

        if ([] === $users || [] === $skus) {
            // 没有外部依赖数据时创建基本测试数据
            $this->createBasicTestData($manager);

            return;
        }

        $cartAddLogs = [];

        // 为每个用户创建一些加购记录
        foreach ($users as $userIndex => $user) {
            // 每个用户使用不同的SKU子集
            $startIndex = $userIndex % count($skus);
            $userSkus = array_slice($skus, $startIndex, min(3, count($skus))); // 每个用户最多3个商品

            foreach ($userSkus as $skuIndex => $sku) {
                // 获取对应的购物车项目ID
                $cartItemId = isset($cartItems[$userIndex * 3 + $skuIndex])
                    ? $cartItems[$userIndex * 3 + $skuIndex]->getId()
                    : 'cart_item_' . uniqid();

                // 创建加购记录
                $cartAddLog = new CartAddLog();
                $cartAddLog->setUser($user);
                $cartAddLog->setSku($sku);
                $cartAddLog->setCartItemId($cartItemId);
                $cartAddLog->setQuantity($skuIndex + 1); // 数量从1开始
                $cartAddLog->setAction(['add', 'update', 'restore'][$skuIndex % 3]); // 不同操作类型
                $cartAddLog->setSkuSnapshot($this->createSkuSnapshot($sku));
                $cartAddLog->setPriceSnapshot($this->createPriceSnapshot());
                $cartAddLog->setMetadata([
                    'source' => 'fixtures',
                    'created_by' => 'system',
                    'test_data' => true,
                ]);

                $manager->persist($cartAddLog);
                $cartAddLogs[] = $cartAddLog;
            }
        }

        $manager->flush();

        // 设置引用以供其他fixtures使用
        foreach ($cartAddLogs as $index => $cartAddLog) {
            $this->addReference("cart_add_log_{$index}", $cartAddLog);
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

    /**
     * @return CartItem[]
     */
    private function getCartItemsFromReferences(): array
    {
        $cartItems = [];

        // 尝试获取CartItem引用
        for ($i = 0; $i < 20; ++$i) {
            try {
                $reference = "cart_item_{$i}";
                if ($this->hasReference($reference, CartItem::class)) {
                    $cartItems[] = $this->getReference($reference, CartItem::class);
                }
            } catch (\Exception $e) {
                // 引用不存在，继续尝试下一个
                continue;
            }
        }

        return $cartItems;
    }

    private function createBasicTestData(ObjectManager $manager): void
    {
        // 创建基本的测试用户
        $testUser = new BizUser();
        $testUser->setUsername('cart_log_fixtures_user_' . uniqid());
        $testUser->setEmail('cartlog@localhost.test');
        $testUser->setPasswordHash('$2y$13$test_hash');
        $manager->persist($testUser);

        // 创建基本的测试SPU和SKU
        $testSpu = new Spu();
        $testSpu->setTitle('Cart Add Log Fixtures Test SPU ' . uniqid());
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
        $cartItem->setQuantity(2);
        $cartItem->setSelected(true);
        $cartItem->setMetadata(['source' => 'basic_cart_log_fixtures']);
        $manager->persist($cartItem);

        // 创建基本的CartAddLog
        $cartAddLog = new CartAddLog();
        $cartAddLog->setUser($testUser);
        $cartAddLog->setSku($testSku);
        $cartAddLog->setCartItemId($cartItem->getId());
        $cartAddLog->setQuantity(2);
        $cartAddLog->setAction('add');
        $cartAddLog->setSkuSnapshot($this->createSkuSnapshot($testSku));
        $cartAddLog->setPriceSnapshot($this->createPriceSnapshot());
        $cartAddLog->setMetadata(['source' => 'basic_fixtures']);

        $manager->persist($cartAddLog);
        $manager->flush();

        // 设置引用
        $this->addReference(self::CART_ADD_LOG_REFERENCE, $cartAddLog);
    }

    /**
     * @return array<string, mixed>
     */
    private function createSkuSnapshot(Sku $sku): array
    {
        return [
            'id' => $sku->getId(),
            'gtin' => $sku->getGtin(),
            'mpn' => $sku->getMpn(),
            'unit' => $sku->getUnit(),
            'valid' => $sku->isValid(),
            'needConsignee' => $sku->isNeedConsignee(),
            'salesReal' => $sku->getSalesReal(),
            'salesVirtual' => $sku->getSalesVirtual(),
            'spu_id' => $sku->getSpu()?->getId(),
            'spu_title' => $sku->getSpu()?->getTitle(),
            'thumbs' => $sku->getThumbs(),
            'snapshot_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createPriceSnapshot(): array
    {
        return [
            'prices' => [
                [
                    'id' => 'price_' . uniqid(),
                    'type' => 'sale',
                    'currency' => 'CNY',
                    'price' => random_int(100, 99999),
                    'taxRate' => 0.13,
                    'priority' => 1,
                    'effectTime' => (new \DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'),
                    'expireTime' => (new \DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s'),
                    'canRefund' => true,
                ],
            ],
            'snapshot_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }
}
