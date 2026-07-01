<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Enum\ActivityStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * @return list<Activity>
     */
    public function findOpenFutureActivities(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.dateActivite >= :today')
            ->setParameter('statut', ActivityStatusEnum::OUVERTE)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('a.dateActivite', 'ASC')
            ->addOrderBy('a.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{activity: Activity, reservationCount: string}>
     */
    public function findMostReserved(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('a AS activity, COUNT(r.id) AS reservationCount')
            ->leftJoin('a.reservations', 'r')
            ->groupBy('a.id')
            ->orderBy('reservationCount', 'DESC')
            ->addOrderBy('a.dateActivite', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
