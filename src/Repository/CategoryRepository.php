<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function hasActivities(Category $category): bool
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(a.id)')
            ->leftJoin('c.activities', 'a')
            ->andWhere('c = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return list<array{category: Category, activityCount: string}>
     */
    public function getActivitiesByCategory(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c AS category, COUNT(a.id) AS activityCount')
            ->leftJoin('c.activities', 'a')
            ->groupBy('c.id')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
