<?php

namespace App\Service;

use App\Entity\Child;
use App\Entity\User;
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

    public function recommendForParent(User $parent, int $limit = 6): array
    {
        $recommendedActivities = [];

        foreach ($parent->getChildren() as $child) {
            foreach ($this->recommendForChild($child) as $activity) {
                $activityId = $activity->getId();
                if (null === $activityId) {
                    continue;
                }

                $recommendedActivities[$activityId] ??= [
                    'activity' => $activity,
                    'children' => [],
                    'childNames' => [],
                ];

                $recommendedActivities[$activityId]['children'][] = $child;
                $recommendedActivities[$activityId]['childNames'][] = $child->getFullName();
            }
        }

        uasort(
            $recommendedActivities,
            static fn (array $left, array $right): int => $left['activity']->getDateActivite() <=> $right['activity']->getDateActivite()
        );

        return array_slice(array_values($recommendedActivities), 0, $limit);
    }
}
