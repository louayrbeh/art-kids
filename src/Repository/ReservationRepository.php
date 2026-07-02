<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ActivityStatusEnum;
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
            ->addSelect('c')
            ->join('c.parent', 'p')
            ->addSelect('p')
            ->join('r.activity', 'a')
            ->addSelect('a')
            ->leftJoin('a.category', 'cat')
            ->addSelect('cat')
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

    public function countByStatus(ReservationStatusEnum $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.statut = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Reservation>
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.child', 'c')
            ->addSelect('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p')
            ->leftJoin('r.activity', 'a')
            ->addSelect('a')
            ->leftJoin('a.category', 'cat')
            ->addSelect('cat')
            ->orderBy('r.dateReservation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{status: string, count: int}>
     */
    public function getReservationsByStatus(): array
    {
        $counts = [];
        foreach (ReservationStatusEnum::cases() as $status) {
            $counts[] = [
                'status' => $status->value,
                'count' => $this->countByStatus($status),
            ];
        }

        return $counts;
    }

    public function countForParent(User $parent): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.child', 'c')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForParentByStatus(User $parent, ReservationStatusEnum $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.child', 'c')
            ->andWhere('c.parent = :parent')
            ->andWhere('r.statut = :status')
            ->setParameter('parent', $parent)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Reservation>
     */
    public function findLatestForParent(User $parent, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.child', 'c')
            ->addSelect('c')
            ->leftJoin('r.activity', 'a')
            ->addSelect('a')
            ->leftJoin('a.category', 'cat')
            ->addSelect('cat')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('r.dateReservation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Reservation>
     */
    public function findUpcomingForParent(User $parent, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.child', 'c')
            ->addSelect('c')
            ->leftJoin('r.activity', 'a')
            ->addSelect('a')
            ->leftJoin('a.category', 'cat')
            ->addSelect('cat')
            ->andWhere('c.parent = :parent')
            ->andWhere('a.dateActivite > :today')
            ->andWhere('a.statut != :cancelledActivity')
            ->andWhere('r.statut != :cancelledReservation')
            ->setParameter('parent', $parent)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelledActivity', ActivityStatusEnum::ANNULEE)
            ->setParameter('cancelledReservation', ReservationStatusEnum::ANNULEE)
            ->orderBy('a.dateActivite', 'ASC')
            ->addOrderBy('a.heureDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUpcomingActivitiesForParent(User $parent): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.child', 'c')
            ->join('r.activity', 'a')
            ->andWhere('c.parent = :parent')
            ->andWhere('a.dateActivite > :today')
            ->andWhere('a.statut != :cancelledActivity')
            ->andWhere('r.statut != :cancelledReservation')
            ->setParameter('parent', $parent)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelledActivity', ActivityStatusEnum::ANNULEE)
            ->setParameter('cancelledReservation', ReservationStatusEnum::ANNULEE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
