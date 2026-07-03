<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ActivityStatusEnum;
use App\Repository\ActivityRepository;

class RecommendationService
{
    public function __construct(private readonly ActivityRepository $activityRepository)
    {
    }

    /**
     * @return list<array{activity: Activity, score: int, reason: string}>
     */
    public function recommendForChild(Child $child, int $limit = 6): array
    {
        $reservedActivityIds = $this->getReservedActivityIds($child);
        $categoryHistory = $this->getCategoryHistory($child);
        $recommendations = [];

        foreach ($this->activityRepository->findOpenFutureActivities() as $activity) {
            $activity->updateStatutIfNeeded();

            if (!$this->isEligibleForChild($child, $activity, $reservedActivityIds)) {
                continue;
            }

            $recommendations[] = [
                'activity' => $activity,
                'score' => $this->calculateScore($child, $activity, $categoryHistory),
                'reason' => $this->getRecommendationReason($child, $activity),
            ];
        }

        usort($recommendations, function (array $left, array $right): int {
            $scoreComparison = $right['score'] <=> $left['score'];
            if (0 !== $scoreComparison) {
                return $scoreComparison;
            }

            $dateComparison = $left['activity']->getDateActivite() <=> $right['activity']->getDateActivite();
            if (0 !== $dateComparison) {
                return $dateComparison;
            }

            $timeComparison = $left['activity']->getHeureDebut() <=> $right['activity']->getHeureDebut();
            if (0 !== $timeComparison) {
                return $timeComparison;
            }

            return strcmp((string) $left['activity']->getTitre(), (string) $right['activity']->getTitre());
        });

        return array_slice($recommendations, 0, max(1, $limit));
    }

    /**
     * @return list<array{child: Child, recommendations: list<array{activity: Activity, score: int, reason: string}>}>
     */
    public function recommendForParent(User $parent, int $limitPerChild = 4): array
    {
        $children = $parent->getChildren()->toArray();
        usort(
            $children,
            static fn (Child $left, Child $right): int => strcmp($left->getFullName(), $right->getFullName())
        );

        $groups = [];
        foreach ($children as $child) {
            $groups[] = [
                'child' => $child,
                'recommendations' => $this->recommendForChild($child, $limitPerChild),
            ];
        }

        return $groups;
    }

    public function getRecommendationReason(Child $child, Activity $activity): string
    {
        $categoryName = $activity->getCategory()?->getNom();
        $activityLabel = $categoryName ? 'Cette activite de '.mb_strtolower($categoryName) : 'Cette activite artistique';
        $age = $child->getAge();
        $ageSentence = sprintf(
            '%s correspond bien a l age de %s (%d ans) avec une tranche prevue de %d a %d ans.',
            $activityLabel,
            $child->getPrenom() ?: 'votre enfant',
            $age,
            $activity->getAgeMin(),
            $activity->getAgeMax()
        );

        $placeSentence = $activity->placesDisponibles() > 1
            ? sprintf('Il reste encore %d places disponibles, ce qui permet une inscription sereine.', $activity->placesDisponibles())
            : 'Une place reste encore disponible pour cette activite.';

        $timingSentence = $this->getDaysUntilActivity($activity) <= 14
            ? 'La date approche sans etre immediate, ce qui en fait une bonne opportunite a planifier.'
            : 'La date a venir laisse le temps de preparer cette experience creative dans de bonnes conditions.';

        return $ageSentence.' '.$placeSentence.' '.$timingSentence;
    }

    /**
     * @param array<int, bool> $reservedActivityIds
     */
    private function isEligibleForChild(Child $child, Activity $activity, array $reservedActivityIds): bool
    {
        $activityId = $activity->getId();
        if (null === $activityId) {
            return false;
        }

        if (isset($reservedActivityIds[$activityId])) {
            return false;
        }

        if (ActivityStatusEnum::OUVERTE !== $activity->getStatut()) {
            return false;
        }

        if (!$activity->estFuture() || $activity->estComplete() || !$activity->estDisponible()) {
            return false;
        }

        $age = $child->getAge();

        return $age >= $activity->getAgeMin() && $age <= $activity->getAgeMax();
    }

    /**
     * @param array<int, int> $categoryHistory
     */
    private function calculateScore(Child $child, Activity $activity, array $categoryHistory): int
    {
        $score = 0;
        $age = $child->getAge();
        $ageMin = (int) $activity->getAgeMin();
        $ageMax = (int) $activity->getAgeMax();
        $ageCenter = ($ageMin + $ageMax) / 2;
        $maxDistance = max(1.0, ($ageMax - $ageMin) / 2 ?: 1.0);
        $distance = abs($age - $ageCenter);
        $score += (int) round(max(0, 40 - (($distance / $maxDistance) * 40)));

        $places = $activity->placesDisponibles();
        if ($places >= 5) {
            $score += 25;
        } elseif ($places >= 3) {
            $score += 18;
        } elseif ($places >= 1) {
            $score += 10;
        }

        $categoryId = $activity->getCategory()?->getId();
        $historyCount = null !== $categoryId ? ($categoryHistory[$categoryId] ?? 0) : 0;
        if (0 === $historyCount) {
            $score += 20;
        } elseif (1 === $historyCount) {
            $score += 10;
        } elseif (2 === $historyCount) {
            $score += 5;
        }

        $daysUntilActivity = $this->getDaysUntilActivity($activity);
        if ($daysUntilActivity >= 2 && $daysUntilActivity <= 14) {
            $score += 15;
        } elseif ($daysUntilActivity <= 30) {
            $score += 10;
        } else {
            $score += 5;
        }

        return $score;
    }

    /**
     * @return array<int, bool>
     */
    private function getReservedActivityIds(Child $child): array
    {
        $reservedActivityIds = [];

        foreach ($child->getReservations() as $reservation) {
            $activityId = $reservation->getActivity()?->getId();
            if (null !== $activityId) {
                $reservedActivityIds[$activityId] = true;
            }
        }

        return $reservedActivityIds;
    }

    /**
     * @return array<int, int>
     */
    private function getCategoryHistory(Child $child): array
    {
        $history = [];

        foreach ($child->getReservations() as $reservation) {
            if (!$reservation instanceof Reservation) {
                continue;
            }

            $categoryId = $reservation->getActivity()?->getCategory()?->getId();
            if (null === $categoryId) {
                continue;
            }

            $history[$categoryId] = ($history[$categoryId] ?? 0) + 1;
        }

        return $history;
    }

    private function getDaysUntilActivity(Activity $activity): int
    {
        $dateActivite = $activity->getDateActivite();
        if (null === $dateActivite) {
            return 999;
        }

        return max(0, (int) (new \DateTimeImmutable('today'))->diff($dateActivite)->days);
    }
}
