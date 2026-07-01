<?php

namespace App\Service;

use App\Repository\ActivityRepository;
use App\Repository\ChildRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;

class StatisticService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ChildRepository $childRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function getSummary(): array
    {
        return [
            'parents' => $this->userRepository->countParents(),
            'children' => $this->childRepository->count([]),
            'activities' => $this->activityRepository->count([]),
            'reservations' => $this->reservationRepository->count([]),
        ];
    }

    public function getMostReservedActivities(int $limit = 5): array
    {
        return $this->activityRepository->findMostReserved($limit);
    }
}
