<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\ReservationStatusEnum;
use App\Repository\ChildRepository;
use App\Repository\ReservationRepository;

class ParentStatisticService
{
    public function __construct(
        private readonly ChildRepository $childRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function countChildrenForParent(User $parent): int
    {
        return $this->childRepository->countForParent($parent);
    }

    public function countReservationsForParent(User $parent): int
    {
        return $this->reservationRepository->countForParent($parent);
    }

    public function countConfirmedReservationsForParent(User $parent): int
    {
        return $this->reservationRepository->countForParentByStatus($parent, ReservationStatusEnum::CONFIRMEE);
    }

    public function countPendingReservationsForParent(User $parent): int
    {
        return $this->reservationRepository->countForParentByStatus($parent, ReservationStatusEnum::EN_ATTENTE);
    }

    public function countUpcomingActivitiesForParent(User $parent): int
    {
        return $this->reservationRepository->countUpcomingActivitiesForParent($parent);
    }

    public function getLatestReservationsForParent(User $parent, int $limit = 10): array
    {
        return $this->reservationRepository->findLatestForParent($parent, $limit);
    }

    public function getUpcomingReservationsForParent(User $parent, int $limit = 5): array
    {
        return $this->reservationRepository->findUpcomingForParent($parent, $limit);
    }

    public function getDashboardData(User $parent): array
    {
        return [
            'childrenCount' => $this->countChildrenForParent($parent),
            'reservationCount' => $this->countReservationsForParent($parent),
            'confirmedReservationsCount' => $this->countConfirmedReservationsForParent($parent),
            'pendingReservationsCount' => $this->countPendingReservationsForParent($parent),
            'upcomingActivitiesCount' => $this->countUpcomingActivitiesForParent($parent),
            'latestReservations' => $this->getLatestReservationsForParent($parent, 8),
            'upcomingReservations' => $this->getUpcomingReservationsForParent($parent, 5),
        ];
    }
}
