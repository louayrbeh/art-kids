<?php

namespace App\Repository;

use App\Entity\Child;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Child>
 */
class ChildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Child::class);
    }

    /**
     * @return list<Child>
     */
    public function findByParent(User $parent): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.nom', 'ASC')
            ->addOrderBy('c.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Child>
     */
    public function findByParentWithRelations(User $parent): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->leftJoin('c.reservations', 'r')
            ->addSelect('r')
            ->leftJoin('r.activity', 'a')
            ->addSelect('a')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.nom', 'ASC')
            ->addOrderBy('c.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Child>
     */
    public function findAllWithParents(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countForParent(User $parent): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
