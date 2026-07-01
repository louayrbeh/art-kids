<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
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
        $reservation->setChild($child);
        $reservation->setActivity($activity);

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->mailerService->sendReservationConfirmation($reservation);

        return $reservation;
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
