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
use App\Repository\ReservationRepository;
use App\Service\MailerService;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ReservationServiceTest extends TestCase
{
    public function testCreateReservationForCompatibleActivity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ReservationRepository::class);
        $mailer = $this->createMock(MailerService::class);

        $child = $this->createChild(7);
        $activity = $this->createActivity('Peinture', 6, 8, 2);

        $repository->method('existsForChildAndActivity')->willReturn(false);
        $repository->method('countActiveForActivity')->willReturn(0);

        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Reservation::class));
        $entityManager->expects(self::once())->method('flush');
        $mailer->expects(self::once())->method('sendReservationConfirmation');

        $service = new ReservationService($entityManager, $repository, $mailer);
        $reservation = $service->createReservation($child, $activity);

        self::assertSame($child, $reservation->getChild());
        self::assertSame($activity, $reservation->getActivity());
        self::assertSame(ReservationStatusEnum::EN_ATTENTE, $reservation->getStatut());
        self::assertSame(1, $activity->placesDisponibles());
    }

    public function testCreateReservationMarksActivityCompleteWhenCapacityIsReached(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ReservationRepository::class);
        $mailer = $this->createMock(MailerService::class);

        $child = $this->createChild(8);
        $activity = $this->createActivity('Atelier unique', 7, 9, 1);

        $repository->method('existsForChildAndActivity')->willReturn(false);
        $repository->method('countActiveForActivity')->willReturn(0);

        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');
        $mailer->expects(self::once())->method('sendReservationConfirmation');

        $service = new ReservationService($entityManager, $repository, $mailer);
        $service->createReservation($child, $activity);

        self::assertSame(0, $activity->placesDisponibles());
        self::assertSame(ActivityStatusEnum::COMPLETE, $activity->getStatut());
    }

    public function testCannotReserveTwiceSameActivity(): void
    {
        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('existsForChildAndActivity')->willReturn(true);

        $service = new ReservationService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $this->createMock(MailerService::class),
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('deja inscrit');

        $service->assertReservationIsAllowed($this->createChild(7), $this->createActivity('Peinture', 6, 8, 3));
    }

    public function testCannotReserveCompleteActivity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('existsForChildAndActivity')->willReturn(false);
        $repository->method('countActiveForActivity')->willReturn(1);

        $service = new ReservationService($entityManager, $repository, $this->createMock(MailerService::class));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('complete');

        $service->assertReservationIsAllowed($this->createChild(7), $this->createActivity('Peinture', 6, 8, 1));
    }

    public function testCannotReserveCancelledPastOrAgeIncompatibleActivity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('existsForChildAndActivity')->willReturn(false);
        $repository->method('countActiveForActivity')->willReturn(0);
        $service = new ReservationService($entityManager, $repository, $this->createMock(MailerService::class));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('pas disponible');

        $service->assertReservationIsAllowed(
            $this->createChild(7),
            $this->createActivity('Annulee', 6, 8, 3, ActivityStatusEnum::ANNULEE)
        );
    }

    public function testCannotReserveOutOfAgeRange(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('existsForChildAndActivity')->willReturn(false);
        $repository->method('countActiveForActivity')->willReturn(0);
        $service = new ReservationService($entityManager, $repository, $this->createMock(MailerService::class));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('n\'est pas compatible');

        $service->assertReservationIsAllowed($this->createChild(5), $this->createActivity('Ados', 8, 10, 4));
    }

    private function createChild(int $age): Child
    {
        $parent = (new User())
            ->setNom('Parent')
            ->setPrenom('Test')
            ->setEmail('parent+'.uniqid().'@test.com')
            ->setRoles([UserRole::ROLE_PARENT->value])
            ->setPassword('hashed-password');

        return (new Child())
            ->setNom('Enfant')
            ->setPrenom('Test')
            ->setDateNaissance(new \DateTimeImmutable(sprintf('-%d years', $age)))
            ->setSexe(SexeEnum::GARCON)
            ->setParent($parent);
    }

    private function createActivity(
        string $title,
        int $ageMin,
        int $ageMax,
        int $capacity,
        ActivityStatusEnum $status = ActivityStatusEnum::OUVERTE,
    ): Activity {
        $category = (new Category())->setNom('Peinture');

        return (new Activity())
            ->setTitre($title)
            ->setDescription('Description de test')
            ->setCategory($category)
            ->setDateActivite(new \DateTimeImmutable('+7 days'))
            ->setHeureDebut(new \DateTimeImmutable('10:00'))
            ->setHeureFin(new \DateTimeImmutable('11:00'))
            ->setCapaciteMax($capacity)
            ->setAgeMin($ageMin)
            ->setAgeMax($ageMax)
            ->setStatut($status);
    }
}
