<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
use App\Enum\UserRole;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
use App\Repository\ChildRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;

class AdminStatisticService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ChildRepository $childRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function countUsers(): int
    {
        return $this->userRepository->count([]);
    }

    public function countParents(): int
    {
        return (int) $this->userRepository
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%'.UserRole::ROLE_PARENT->value.'%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAdmins(): int
    {
        return (int) $this->userRepository
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%'.UserRole::ROLE_ADMIN->value.'%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countChildren(): int
    {
        return $this->childRepository->count([]);
    }

    public function countCategories(): int
    {
        return $this->categoryRepository->count([]);
    }

    public function countActivities(): int
    {
        return $this->activityRepository->count([]);
    }

    public function countReservations(): int
    {
        return $this->reservationRepository->count([]);
    }

    public function countReservationsByStatus(string $status): int
    {
        return (int) $this->reservationRepository
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.statut = :status')
            ->setParameter('status', $this->reservationStatus($status))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActivitiesByStatus(string $status): int
    {
        return (int) $this->activityRepository
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.statut = :status')
            ->setParameter('status', $this->activityStatus($status))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUpcomingActivities(): int
    {
        return (int) $this->activityRepository
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.dateActivite > :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPastActivities(): int
    {
        return (int) $this->activityRepository
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.dateActivite <= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{label: string, value: int, status: string}>
     */
    public function getReservationsByStatus(): array
    {
        $rows = [];
        foreach (ReservationStatusEnum::cases() as $status) {
            $rows[] = [
                'label' => $status->label(),
                'value' => $this->countReservationsByStatus($status->value),
                'status' => $status->value,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, value: int, status: string}>
     */
    public function getActivitiesByStatus(): array
    {
        $rows = [];
        foreach (ActivityStatusEnum::cases() as $status) {
            $rows[] = [
                'label' => $status->label(),
                'value' => $this->countActivitiesByStatus($status->value),
                'status' => $status->value,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function getReservationsByMonth(int $months = 12): array
    {
        $months = max(1, $months);
        $start = (new \DateTimeImmutable('first day of this month'))->modify(sprintf('-%d months', $months - 1));
        $results = $this->reservationRepository
            ->createQueryBuilder('r')
            ->select('r.dateReservation')
            ->andWhere('r.dateReservation >= :start')
            ->setParameter('start', $start)
            ->orderBy('r.dateReservation', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $buckets = [];
        for ($index = 0; $index < $months; ++$index) {
            $monthDate = $start->modify(sprintf('+%d months', $index));
            $key = $monthDate->format('Y-m');
            $buckets[$key] = [
                'label' => $monthDate->format('m/Y'),
                'value' => 0,
            ];
        }

        foreach ($results as $row) {
            $dateValue = $row['dateReservation'] ?? null;
            if (!$dateValue instanceof \DateTimeInterface) {
                continue;
            }

            $key = $dateValue->format('Y-m');
            if (isset($buckets[$key])) {
                ++$buckets[$key]['value'];
            }
        }

        return array_values($buckets);
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function getActivitiesByCategory(): array
    {
        $results = $this->activityRepository
            ->createQueryBuilder('a')
            ->select('c.nom AS label, COUNT(a.id) AS value')
            ->join('a.category', 'c')
            ->groupBy('c.id')
            ->orderBy('value', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'label' => (string) $row['label'],
                'value' => (int) $row['value'],
            ],
            $results
        );
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function getUsersByRole(): array
    {
        return [
            ['label' => 'Parents', 'value' => $this->countParents()],
            ['label' => 'Administrateurs', 'value' => $this->countAdmins()],
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function getTopReservedActivities(int $limit = 5): array
    {
        return array_map(
            static fn (array $item): array => [
                'label' => $item['label'],
                'value' => $item['value'],
            ],
            $this->getTopReservedActivitiesDetailed($limit)
        );
    }

    /**
     * @return list<array{label: string, value: int, activity: Activity, reservedPlaces: int, fillRate: int}>
     */
    public function getTopReservedActivitiesDetailed(int $limit = 5): array
    {
        $results = $this->activityRepository
            ->createQueryBuilder('a')
            ->select('a AS activity, COUNT(r.id) AS reservedPlaces')
            ->leftJoin('a.reservations', 'r', 'WITH', 'r.statut != :cancelled')
            ->setParameter('cancelled', ReservationStatusEnum::ANNULEE)
            ->groupBy('a.id')
            ->having('COUNT(r.id) > 0')
            ->orderBy('reservedPlaces', 'DESC')
            ->addOrderBy('a.dateActivite', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(function (array $row): array {
            /** @var Activity $activity */
            $activity = $row['activity'];
            $reservedPlaces = (int) $row['reservedPlaces'];
            $capacity = max(1, (int) $activity->getCapaciteMax());

            return [
                'label' => (string) $activity->getTitre(),
                'value' => $reservedPlaces,
                'activity' => $activity,
                'reservedPlaces' => $reservedPlaces,
                'fillRate' => (int) round(($reservedPlaces / $capacity) * 100),
            ];
        }, $results);
    }

    /**
     * @return array{activity: Activity, value: int, fillRate: int}|null
     */
    public function getMostReservedActivity(): ?array
    {
        $top = $this->getTopReservedActivitiesDetailed(1);

        if ([] === $top) {
            return null;
        }

        return [
            'activity' => $top[0]['activity'],
            'value' => $top[0]['value'],
            'fillRate' => $top[0]['fillRate'],
        ];
    }

    /**
     * @return array{category: string, value: int}|null
     */
    public function getMostPopularCategory(): ?array
    {
        $row = $this->reservationRepository
            ->createQueryBuilder('r')
            ->select('c.nom AS category, COUNT(r.id) AS value')
            ->join('r.activity', 'a')
            ->join('a.category', 'c')
            ->andWhere('r.statut != :cancelled')
            ->setParameter('cancelled', ReservationStatusEnum::ANNULEE)
            ->groupBy('c.id')
            ->orderBy('value', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row)) {
            return null;
        }

        return [
            'category' => (string) $row['category'],
            'value' => (int) $row['value'],
        ];
    }

    /**
     * @return array{user: User, value: int}|null
     */
    public function getMostActiveParent(): ?array
    {
        $row = $this->userRepository
            ->createQueryBuilder('u')
            ->select('u AS user, COUNT(r.id) AS value')
            ->join('u.children', 'c')
            ->join('c.reservations', 'r')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%'.UserRole::ROLE_PARENT->value.'%')
            ->groupBy('u.id')
            ->orderBy('value', 'DESC')
            ->addOrderBy('u.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row) || !$row['user'] instanceof User) {
            return null;
        }

        return [
            'user' => $row['user'],
            'value' => (int) $row['value'],
        ];
    }

    /**
     * @return array{child: \App\Entity\Child, value: int}|null
     */
    public function getMostActiveChild(): ?array
    {
        $row = $this->childRepository
            ->createQueryBuilder('c')
            ->select('c AS child, COUNT(r.id) AS value')
            ->join('c.reservations', 'r')
            ->groupBy('c.id')
            ->orderBy('value', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row) || !$row['child'] instanceof \App\Entity\Child) {
            return null;
        }

        return [
            'child' => $row['child'],
            'value' => (int) $row['value'],
        ];
    }

    public function getLatestReservation(): ?Reservation
    {
        return $this->reservationRepository->findOneBy([], ['dateReservation' => 'DESC']);
    }

    public function getLatestParent(): ?User
    {
        return $this->userRepository
            ->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%'.UserRole::ROLE_PARENT->value.'%')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAverageActivityFillRate(): float
    {
        $totalCapacity = (float) $this->activityRepository
            ->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.capaciteMax), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalCapacity <= 0) {
            return 0.0;
        }

        $confirmedReservations = (float) $this->reservationRepository
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.statut = :status')
            ->setParameter('status', ReservationStatusEnum::CONFIRMEE)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($confirmedReservations / $totalCapacity) * 100, 1);
    }

    public function getTotalAvailablePlaces(): int
    {
        $activities = $this->activityRepository
            ->createQueryBuilder('a')
            ->leftJoin('a.reservations', 'r')
            ->addSelect('r')
            ->andWhere('a.dateActivite > :today')
            ->andWhere('a.statut != :cancelled')
            ->andWhere('a.statut != :finished')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelled', ActivityStatusEnum::ANNULEE)
            ->setParameter('finished', ActivityStatusEnum::TERMINEE)
            ->getQuery()
            ->getResult();

        $total = 0;
        foreach ($activities as $activity) {
            if ($activity instanceof Activity) {
                $total += $activity->placesDisponibles();
            }
        }

        return $total;
    }

    public function getTotalReservedPlaces(): int
    {
        return (int) $this->reservationRepository
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.statut != :cancelled')
            ->setParameter('cancelled', ReservationStatusEnum::ANNULEE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{activity: Activity, reservedPlaces: int, remainingPlaces: int, fillRate: int}>
     */
    public function getAlmostFullActivities(int $limit = 5): array
    {
        $activities = $this->activityRepository
            ->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->leftJoin('a.reservations', 'r')
            ->addSelect('r')
            ->andWhere('a.dateActivite > :today')
            ->andWhere('a.statut != :cancelled')
            ->andWhere('a.statut != :finished')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelled', ActivityStatusEnum::ANNULEE)
            ->setParameter('finished', ActivityStatusEnum::TERMINEE)
            ->orderBy('a.dateActivite', 'ASC')
            ->addOrderBy('a.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $almostFull = [];

        foreach ($activities as $activity) {
            if (!$activity instanceof Activity) {
                continue;
            }

            $reservedPlaces = $activity->getCapaciteMax() - $activity->placesDisponibles();
            $remainingPlaces = $activity->placesDisponibles();
            $fillRate = $activity->getCapaciteMax() > 0
                ? (int) round(($reservedPlaces / $activity->getCapaciteMax()) * 100)
                : 0;

            if ($remainingPlaces <= 3 || $fillRate >= 80) {
                $almostFull[] = [
                    'activity' => $activity,
                    'reservedPlaces' => $reservedPlaces,
                    'remainingPlaces' => $remainingPlaces,
                    'fillRate' => $fillRate,
                ];
            }
        }

        usort($almostFull, static function (array $left, array $right): int {
            $fillComparison = $right['fillRate'] <=> $left['fillRate'];
            if (0 !== $fillComparison) {
                return $fillComparison;
            }

            return $left['remainingPlaces'] <=> $right['remainingPlaces'];
        });

        return array_slice($almostFull, 0, max(1, $limit));
    }

    /**
     * @return list<Reservation>
     */
    public function getLatestReservations(int $limit = 8): array
    {
        return $this->reservationRepository->findLatest($limit);
    }

    /**
     * @return list<Activity>
     */
    public function getUpcomingActivities(int $limit = 8): array
    {
        return $this->activityRepository
            ->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->andWhere('a.dateActivite > :today')
            ->andWhere('a.statut != :cancelled')
            ->andWhere('a.statut != :finished')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('cancelled', ActivityStatusEnum::ANNULEE)
            ->setParameter('finished', ActivityStatusEnum::TERMINEE)
            ->orderBy('a.dateActivite', 'ASC')
            ->addOrderBy('a.heureDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getDashboardData(): array
    {
        return [
            'totalUsers' => $this->countUsers(),
            'totalParents' => $this->countParents(),
            'totalAdmins' => $this->countAdmins(),
            'totalChildren' => $this->countChildren(),
            'totalCategories' => $this->countCategories(),
            'totalActivities' => $this->countActivities(),
            'totalReservations' => $this->countReservations(),
            'pendingReservations' => $this->countReservationsByStatus(ReservationStatusEnum::EN_ATTENTE->value),
            'confirmedReservations' => $this->countReservationsByStatus(ReservationStatusEnum::CONFIRMEE->value),
            'cancelledReservations' => $this->countReservationsByStatus(ReservationStatusEnum::ANNULEE->value),
            'openActivities' => $this->countActivitiesByStatus(ActivityStatusEnum::OUVERTE->value),
            'completeActivities' => $this->countActivitiesByStatus(ActivityStatusEnum::COMPLETE->value),
            'cancelledActivitiesCount' => $this->countActivitiesByStatus(ActivityStatusEnum::ANNULEE->value),
            'finishedActivities' => $this->countActivitiesByStatus(ActivityStatusEnum::TERMINEE->value),
            'upcomingActivitiesCount' => $this->countUpcomingActivities(),
            'pastActivitiesCount' => $this->countPastActivities(),
            'reservationsByStatus' => $this->getReservationsByStatus(),
            'activitiesByStatus' => $this->getActivitiesByStatus(),
            'reservationsByMonth' => $this->getReservationsByMonth(),
            'activitiesByCategory' => $this->getActivitiesByCategory(),
            'usersByRole' => $this->getUsersByRole(),
            'topReservedActivities' => $this->getTopReservedActivities(),
            'topReservedActivitiesDetailed' => $this->getTopReservedActivitiesDetailed(),
            'mostReservedActivity' => $this->getMostReservedActivity(),
            'mostPopularCategory' => $this->getMostPopularCategory(),
            'mostActiveParent' => $this->getMostActiveParent(),
            'mostActiveChild' => $this->getMostActiveChild(),
            'latestReservation' => $this->getLatestReservation(),
            'latestParent' => $this->getLatestParent(),
            'averageFillRate' => $this->getAverageActivityFillRate(),
            'totalAvailablePlaces' => $this->getTotalAvailablePlaces(),
            'totalReservedPlaces' => $this->getTotalReservedPlaces(),
            'almostFullActivities' => $this->getAlmostFullActivities(),
            'latestReservations' => $this->getLatestReservations(),
            'upcomingActivities' => $this->getUpcomingActivities(),
        ];
    }

    private function reservationStatus(string $status): ReservationStatusEnum
    {
        return ReservationStatusEnum::tryFrom($status)
            ?? throw new \InvalidArgumentException(sprintf('Statut de reservation invalide : %s', $status));
    }

    private function activityStatus(string $status): ActivityStatusEnum
    {
        return ActivityStatusEnum::tryFrom($status)
            ?? throw new \InvalidArgumentException(sprintf('Statut d activite invalide : %s', $status));
    }
}
