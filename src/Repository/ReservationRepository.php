<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ReservationStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function existsForChildAndActivity(Child $child, Activity $activity): bool
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.child = :child')
            ->andWhere('r.activity = :activity')
            ->setParameter('child', $child)
            ->setParameter('activity', $activity)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return list<Reservation>
     */
    public function findByParent(User $parent): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.child', 'c')
            ->join('r.activity', 'a')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveForActivity(Activity $activity): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.activity = :activity')
            ->andWhere('r.statut != :cancelled')
            ->setParameter('activity', $activity)
            ->setParameter('cancelled', ReservationStatusEnum::ANNULEE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
