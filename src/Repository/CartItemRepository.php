<?php

declare(strict_types=1);

namespace Tourze\OrderCartBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderCartBundle\Entity\CartItem;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
#[AsRepository(entityClass: CartItem::class)]
#[Autoconfigure(public: true)]
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function save(CartItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CartItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<CartItem>
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartItem> $result */
    }

    public function findByUserAndId(UserInterface $user, string $id): ?CartItem
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        /** @var CartItem|null $result */
    }

    /**
     * @param array<string> $ids
     * @return array<CartItem>
     */
    public function findByUserAndIds(UserInterface $user, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartItem> $result */
    }

    public function findByUserAndSku(UserInterface $user, Sku $sku): ?CartItem
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.sku = :sku')
            ->setParameter('user', $user)
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        /** @var CartItem|null $result */
    }

    public function countByUser(UserInterface $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array<CartItem>
     */
    public function findSelectedByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.selected = :selected')
            ->setParameter('user', $user)
            ->setParameter('selected', true)
            ->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<CartItem> $result */
    }

    public function getTotalQuantityByUser(UserInterface $user): int
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.quantity)')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * @param array<string> $itemIds
     */
    public function batchUpdateCheckedStatus(UserInterface $user, array $itemIds, bool $checked): int
    {
        if ([] === $itemIds) {
            return 0;
        }

        $result = $this->createQueryBuilder('c')
            ->update()
            ->set('c.selected', ':checked')
            ->set('c.updateTime', ':now')
            ->andWhere('c.user = :user')
            ->andWhere('c.id IN (:itemIds)')
            ->setParameter('checked', $checked)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->setParameter('itemIds', $itemIds)
            ->getQuery()
            ->execute()
        ;

        return \is_int($result) ? $result : 0;
    }

    public function updateAllCheckedStatus(UserInterface $user, bool $checked): int
    {
        $result = $this->createQueryBuilder('c')
            ->update()
            ->set('c.selected', ':checked')
            ->set('c.updateTime', ':now')
            ->andWhere('c.user = :user')
            ->setParameter('checked', $checked)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;

        return \is_int($result) ? $result : 0;
    }

    /**
     * @param array<string> $itemIds
     */
    public function batchDelete(UserInterface $user, array $itemIds): int
    {
        if ([] === $itemIds) {
            return 0;
        }

        $result = $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.user = :user')
            ->andWhere('c.id IN (:itemIds)')
            ->setParameter('user', $user)
            ->setParameter('itemIds', $itemIds)
            ->getQuery()
            ->execute()
        ;

        return \is_int($result) ? $result : 0;
    }
}
