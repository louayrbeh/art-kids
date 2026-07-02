<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ReservationStatusEnum;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReservationRepository $reservationRepository,
        private readonly MailerService $mailerService,
    ) {
    }

    public function createReservation(Child $child, Activity $activity): Reservation
    {
        $this->assertReservationIsAllowed($child, $activity);

        $reservation = new Reservation();
        $reservation
            ->setChild($child)
            ->setActivity($activity)
            ->setDateReservation(new \DateTimeImmutable())
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->mailerService->sendReservationConfirmation($reservation);

        return $reservation;
    }

    public function canReserve(Child $child, Activity $activity): bool
    {
        try {
            $this->assertReservationIsAllowed($child, $activity);

            return true;
        } catch (\DomainException) {
            return false;
        }
    }

    public function cancelReservation(Reservation $reservation): void
    {
        if ($reservation->estAnnulee()) {
            throw new \DomainException('Cette reservation est deja annulee.');
        }

        if (ReservationStatusEnum::TERMINEE === $reservation->getStatut()) {
            throw new \DomainException('Une reservation terminee ne peut plus etre annulee.');
        }

        $activity = $reservation->getActivity();
        if (null !== $activity && !$activity->estFuture()) {
            throw new \DomainException('Impossible d annuler une reservation pour une activite deja passee.');
        }

        $reservation->annuler();
        $reservation->setUpdatedAt(new \DateTimeImmutable());
        $activity?->updateStatutIfNeeded();
        $this->entityManager->flush();
    }

    public function assertReservationIsAllowed(Child $child, Activity $activity, ?User $expectedParent = null): void
    {
        if (null !== $expectedParent && $child->getParent() !== $expectedParent) {
            throw new \DomainException('Vous ne pouvez reserver que pour vos propres enfants.');
        }

        $activity->updateStatutIfNeeded();

        if (!$activity->estDisponible()) {
            throw new \DomainException('Cette activite n\'est pas disponible a la reservation.');
        }

        if ($this->reservationRepository->existsForChildAndActivity($child, $activity)) {
            throw new \DomainException('Cet enfant est deja inscrit a cette activite.');
        }

        $age = $child->getAge();
        if ($age < $activity->getAgeMin() || $age > $activity->getAgeMax()) {
            throw new \DomainException('L\'age de l\'enfant n\'est pas compatible avec cette activite.');
        }

        if ($this->reservationRepository->countActiveForActivity($activity) >= $activity->getCapaciteMax()) {
            throw new \DomainException('Cette activite est complete.');
        }
    }
}
