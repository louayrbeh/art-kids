<?php

namespace App\Service;

use App\Entity\Child;
use App\Repository\ActivityRepository;

class RecommendationService
{
    public function __construct(private readonly ActivityRepository $activityRepository)
    {
    }

    public function recommendForChild(Child $child): array
    {
        return array_values(array_filter(
            $this->activityRepository->findOpenFutureActivities(),
            static fn ($activity): bool => $child->getAge() >= $activity->getAgeMin() && $child->getAge() <= $activity->getAgeMax() && $activity->estDisponible()
        ));
    }
}
