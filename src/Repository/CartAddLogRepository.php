<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartAddLog;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @extends ServiceEntityRepository<CartAddLog>
 */
#[AsRepository(entityClass: CartAddLog::class)]
#[Autoconfigure(public: true)]
class CartAddLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartAddLog::class);
    }

    public function save(CartAddLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CartAddLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据用户查找加购记录
     *
     * @return array<CartAddLog>
     */
    public function findByUser(UserInterface $user, int $limit = 100): array
    {
        return $this->createQueryBuilder('log')
            ->andWhere('log.user = :user')
            ->setParameter('user', $user)
            ->orderBy('log.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartAddLog> $result */
    }

    /**
     * 根据用户和SKU查找加购记录
     *
     * @return array<CartAddLog>
     */
    public function findByUserAndSku(UserInterface $user, Sku $sku): array
    {
        return $this->createQueryBuilder('log')
            ->andWhere('log.user = :user')
            ->andWhere('log.sku = :sku')
            ->setParameter('user', $user)
            ->setParameter('sku', $sku)
            ->orderBy('log.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartAddLog> $result */
    }

    /**
     * 根据购物车项ID查找加购记录
     *
     * @return array<CartAddLog>
     */
    public function findByCartItemId(string $cartItemId): array
    {
        return $this->createQueryBuilder('log')
            ->andWhere('log.cartItemId = :cartItemId')
            ->setParameter('cartItemId', $cartItemId)
            ->orderBy('log.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartAddLog> $result */
    }

    /**
     * 根据购物车项ID列表查找加购记录
     *
     * @param array<string> $cartItemIds
     * @return array<CartAddLog>
     */
    public function findByCartItemIds(array $cartItemIds): array
    {
        if ([] === $cartItemIds) {
            return [];
        }

        return $this->createQueryBuilder('log')
            ->andWhere('log.cartItemId IN (:cartItemIds)')
            ->setParameter('cartItemIds', $cartItemIds)
            ->orderBy('log.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartAddLog> $result */
    }

    /**
     * 标记购物车项对应的加购记录为已删除
     *
     * @param array<string> $cartItemIds
     */
    public function markAsDeletedByCartItemIds(array $cartItemIds): int
    {
        if ([] === $cartItemIds) {
            return 0;
        }

        $result = $this->createQueryBuilder('log')
            ->update()
            ->set('log.isDeleted', ':deleted')
            ->set('log.deleteTime', ':deleteTime')
            ->set('log.updateTime', ':updateTime')
            ->andWhere('log.cartItemId IN (:cartItemIds)')
            ->andWhere('log.isDeleted = :notDeleted')
            ->setParameter('deleted', true)
            ->setParameter('notDeleted', false)
            ->setParameter('deleteTime', new \DateTimeImmutable())
            ->setParameter('updateTime', new \DateTimeImmutable())
            ->setParameter('cartItemIds', $cartItemIds)
            ->getQuery()
            ->execute()
        ;

        return \is_int($result) ? $result : 0;
    }

    /**
     * 统计用户加购次数
     */
    public function countByUser(UserInterface $user): int
    {
        return (int) $this->createQueryBuilder('log')
            ->select('COUNT(log.id)')
            ->andWhere('log.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 统计用户对指定SKU的加购次数
     */
    public function countByUserAndSku(UserInterface $user, Sku $sku): int
    {
        return (int) $this->createQueryBuilder('log')
            ->select('COUNT(log.id)')
            ->andWhere('log.user = :user')
            ->andWhere('log.sku = :sku')
            ->setParameter('user', $user)
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 统计用户总加购数量
     */
    public function sumQuantityByUser(UserInterface $user): int
    {
        $result = $this->createQueryBuilder('log')
            ->select('SUM(log.quantity)')
            ->andWhere('log.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * 获取用户最近的加购记录
     *
     * @return array<CartAddLog>
     */
    public function findRecentByUser(UserInterface $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('log')
            ->andWhere('log.user = :user')
            ->setParameter('user', $user)
            ->orderBy('log.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartAddLog> $result */
    }

    /**
     * 根据操作类型查找记录
     *
     * @return array<CartAddLog>
     */
    public function findByUserAndAction(UserInterface $user, string $action, int $limit = 50): array
    {
        return $this->createQueryBuilder('log')
            ->andWhere('log.user = :user')
            ->andWhere('log.action = :action')
            ->setParameter('user', $user)
            ->setParameter('action', $action)
            ->orderBy('log.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartAddLog> $result */
    }

    /**
     * 清理旧的加购记录（可用于定期清理）
     */
    public function deleteOldLogs(\DateTimeInterface $before): int
    {
        $result = $this->createQueryBuilder('log')
            ->delete()
            ->andWhere('log.createTime < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;

        return \is_int($result) ? $result : 0;
    }
}
