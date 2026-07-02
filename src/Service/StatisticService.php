<?php

namespace App\Service;

use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
use App\Repository\ChildRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;

class StatisticService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ChildRepository $childRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function countParents(): int
    {
        return $this->userRepository->countParents();
    }

    public function countAdmins(): int
    {
        return $this->userRepository->countAdmins();
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

    public function countPendingReservations(): int
    {
        return $this->reservationRepository->countByStatus(ReservationStatusEnum::EN_ATTENTE);
    }

    public function getSummary(): array
    {
        return [
            'parents' => $this->countParents(),
            'admins' => $this->countAdmins(),
            'children' => $this->countChildren(),
            'categories' => $this->countCategories(),
            'activities' => $this->countActivities(),
            'reservations' => $this->countReservations(),
            'pendingReservations' => $this->countPendingReservations(),
        ];
    }

    public function getLatestReservations(int $limit = 10): array
    {
        return $this->reservationRepository->findLatest($limit);
    }

    public function getPopularActivities(int $limit = 5): array
    {
        return array_map(
            static function (array $row): array {
                $activity = $row['activity'];
                $count = (int) $row['reservationCount'];
                $capacity = max(1, (int) $activity->getCapaciteMax());

                return [
                    'activity' => $activity,
                    'reservationCount' => $count,
                    'fillRate' => (int) round(($count / $capacity) * 100),
                ];
            },
            $this->activityRepository->findMostReserved($limit)
        );
    }

    public function getMostReservedActivities(int $limit = 5): array
    {
        return $this->getPopularActivities($limit);
    }

    public function getReservationsByStatus(): array
    {
        return $this->reservationRepository->getReservationsByStatus();
    }

    public function getActivitiesByCategory(): array
    {
        return array_map(
            static fn (array $row): array => [
                'label' => $row['category']->getNom(),
                'count' => (int) $row['activityCount'],
            ],
            $this->categoryRepository->getActivitiesByCategory()
        );
    }

    public function getUsersByRole(): array
    {
        return $this->userRepository->getUsersByRole();
    }

    public function getFullActivities(): array
    {
        return $this->activityRepository->findByStatus(ActivityStatusEnum::COMPLETE);
    }

    public function getCancelledActivities(): array
    {
        return $this->activityRepository->findByStatus(ActivityStatusEnum::ANNULEE);
    }

    public function getUpcomingActivities(int $days = 7): array
    {
        return $this->activityRepository->findUpcomingWithinDays($days);
    }

    public function getDisabledUsers(): array
    {
        return $this->userRepository->findDisabledUsers();
    }

    public function getDashboardData(): array
    {
        return [
            'totalParents' => $this->countParents(),
            'totalAdmins' => $this->countAdmins(),
            'totalChildren' => $this->countChildren(),
            'totalCategories' => $this->countCategories(),
            'totalActivities' => $this->countActivities(),
            'totalReservations' => $this->countReservations(),
            'pendingReservations' => $this->countPendingReservations(),
            'latestReservations' => $this->getLatestReservations(),
            'popularActivities' => $this->getPopularActivities(),
            'reservationsByStatus' => $this->getReservationsByStatus(),
            'activitiesByCategory' => $this->getActivitiesByCategory(),
            'usersByRole' => $this->getUsersByRole(),
            'fullActivities' => $this->getFullActivities(),
            'cancelledActivities' => $this->getCancelledActivities(),
            'upcomingActivities' => $this->getUpcomingActivities(),
            'disabledUsers' => $this->getDisabledUsers(),
        ];
    }
}
