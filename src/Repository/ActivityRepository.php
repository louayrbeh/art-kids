<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
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
            ->andWhere('a.dateActivite > :today')
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
            ->leftJoin('a.reservations', 'r', 'WITH', 'r.statut != :cancelled')
            ->setParameter('cancelled', ReservationStatusEnum::ANNULEE)
            ->groupBy('a.id')
            ->orderBy('reservationCount', 'DESC')
            ->addOrderBy('a.dateActivite', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasActiveReservations(Activity $activity): bool
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(r.id)')
            ->leftJoin('a.reservations', 'r')
            ->andWhere('a = :activity')
            ->andWhere('r.statut != :cancelled')
            ->setParameter('activity', $activity)
            ->setParameter('cancelled', ReservationStatusEnum::ANNULEE)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function countByStatus(ActivityStatusEnum $status): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.statut = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Activity>
     */
    public function findByStatus(ActivityStatusEnum $status): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->andWhere('a.statut = :status')
            ->setParameter('status', $status)
            ->orderBy('a.dateActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Activity>
     */
    public function findUpcomingWithinDays(int $days = 7): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->andWhere('a.dateActivite > :today')
            ->andWhere('a.dateActivite <= :limit')
            ->andWhere('a.statut != :cancelled')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('limit', new \DateTimeImmutable(sprintf('+%d days', $days)))
            ->setParameter('cancelled', ActivityStatusEnum::ANNULEE)
            ->orderBy('a.dateActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
