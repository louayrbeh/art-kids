<?php

namespace App\Tests\Unit;

use App\Entity\Activity;
use App\Entity\Category;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
use App\Enum\SexeEnum;
use App\Enum\UserRole;
use App\Repository\ActivityRepository;
use App\Service\RecommendationService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RecommendationServiceTest extends TestCase
{
    private int $activityIdSequence = 1;

    public function testRecommendOnlyEligibleActivitiesSortedByRelevance(): void
    {
        $child = $this->createChild(8);

        $peinture = (new Category())->setNom('Peinture');
        $musique = (new Category())->setNom('Musique');

        $alreadyReserved = $this->createActivity('Deja reservee', $peinture, '+6 days', 5, 7, 9, 8);
        $reservation = (new Reservation())->setStatut(ReservationStatusEnum::CONFIRMEE);
        $child->addReservation($reservation);
        $alreadyReserved->addReservation($reservation);

        $bestMatch = $this->createActivity('Peinture ideale', $musique, '+5 days', 8, 7, 9, 10);
        $lowerMatch = $this->createActivity('Places limitees', $musique, '+20 days', 3, 7, 9, 3);
        $tooYoung = $this->createActivity('Pour plus grands', $musique, '+7 days', 8, 10, 12, 10);
        $complete = $this->createActivity('Complete', $musique, '+8 days', 1, 7, 9, 1);
        $complete->addReservation((new Reservation())->setStatut(ReservationStatusEnum::CONFIRMEE)->setChild($this->createChild(8)));
        $cancelled = $this->createActivity('Annulee', $musique, '+9 days', 10, 7, 9, 10, ActivityStatusEnum::ANNULEE);
        $past = $this->createActivity('Passee', $musique, '-2 days', 10, 7, 9, 10);

        $repository = $this->createMock(ActivityRepository::class);
        $repository->method('findOpenFutureActivities')->willReturn([
            $alreadyReserved,
            $bestMatch,
            $lowerMatch,
            $tooYoung,
            $complete,
            $cancelled,
            $past,
        ]);

        $service = new RecommendationService($repository);
        $results = $service->recommendForChild($child, 6);

        self::assertCount(2, $results);
        self::assertSame('Peinture ideale', $results[0]['activity']->getTitre());
        self::assertSame('Places limitees', $results[1]['activity']->getTitre());
        self::assertGreaterThan($results[1]['score'], $results[0]['score']);
    }

    public function testRecommendationReasonIsNeverEmpty(): void
    {
        $child = $this->createChild(7);
        $activity = $this->createActivity('Atelier peinture', (new Category())->setNom('Peinture'), '+10 days', 6, 6, 8, 8);

        $service = new RecommendationService($this->createMock(ActivityRepository::class));
        $reason = $service->getRecommendationReason($child, $activity);

        self::assertNotSame('', trim($reason));
        self::assertStringContainsString('age', mb_strtolower($reason));
    }

    private function createChild(int $age): Child
    {
        $parent = (new User())
            ->setNom('Parent')
            ->setPrenom('Test')
            ->setEmail('parent+'.uniqid().'@test.com')
            ->setRoles([UserRole::ROLE_PARENT->value])
            ->setPassword('hashed');

        return (new Child())
            ->setNom('Enfant')
            ->setPrenom('Test')
            ->setDateNaissance(new \DateTimeImmutable(sprintf('-%d years', $age)))
            ->setSexe(SexeEnum::GARCON)
            ->setParent($parent);
    }

    private function createActivity(
        string $title,
        Category $category,
        string $date,
        int $availablePlaces,
        int $ageMin,
        int $ageMax,
        int $capacity,
        ActivityStatusEnum $status = ActivityStatusEnum::OUVERTE,
    ): Activity {
        $activity = (new Activity())
            ->setTitre($title)
            ->setDescription('Description')
            ->setCategory($category)
            ->setDateActivite(new \DateTimeImmutable($date))
            ->setHeureDebut(new \DateTimeImmutable('10:00'))
            ->setHeureFin(new \DateTimeImmutable('11:00'))
            ->setCapaciteMax($capacity)
            ->setAgeMin($ageMin)
            ->setAgeMax($ageMax)
            ->setStatut($status);

        $reflectionProperty = new \ReflectionProperty(Activity::class, 'id');
        $reflectionProperty->setValue($activity, $this->activityIdSequence++);

        $reservationsToAdd = max(0, $capacity - $availablePlaces);
        for ($i = 0; $i < $reservationsToAdd; ++$i) {
            $dummyChild = $this->createChild(8);
            $reservation = (new Reservation())->setStatut(ReservationStatusEnum::CONFIRMEE);
            $dummyChild->addReservation($reservation);
            $activity->addReservation($reservation);
        }

        return $activity;
    }
}
