<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiController;
use App\Entity\Reservation;
use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
use App\Repository\ReservationRepository;
use App\Service\Api\ApiResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/reservations', name: 'api_admin_reservations_')]
class ReservationApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success(array_map(
            fn (Reservation $reservation): array => $responseFactory->reservation($reservation, true),
            $reservationRepository->findLatest(50)
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Reservation $reservation, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success($responseFactory->reservation($reservation, true));
    }

    #[Route('/{id}/confirm', name: 'confirm', methods: ['PUT'])]
    public function confirm(Reservation $reservation, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        $activity = $reservation->getActivity();
        if (null === $activity) {
            return $responseFactory->error('Reservation invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity->updateStatutIfNeeded();
        if ($reservation->estAnnulee()) {
            return $responseFactory->error('Une reservation annulee ne peut pas etre confirmee.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$activity->estFuture() || in_array($activity->getStatut(), [ActivityStatusEnum::ANNULEE, ActivityStatusEnum::TERMINEE], true)) {
            return $responseFactory->error('Cette activite n est plus disponible pour confirmation.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($reservationRepository->countActiveForActivity($activity) > (int) $activity->getCapaciteMax()) {
            return $responseFactory->error('La capacite maximale de cette activite est deja depassee.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reservation->confirmer();
        $activity->updateStatutIfNeeded();
        $entityManager->flush();

        return $responseFactory->success($responseFactory->reservation($reservation, true));
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['PUT'])]
    public function cancel(Reservation $reservation, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        if ($reservation->estAnnulee()) {
            return $responseFactory->success($responseFactory->reservation($reservation, true));
        }

        $reservation->annuler();
        $reservation->getActivity()?->updateStatutIfNeeded();
        $entityManager->flush();

        return $responseFactory->success($responseFactory->reservation($reservation, true));
    }

    #[Route('/{id}/finish', name: 'finish', methods: ['PUT'])]
    public function finish(Reservation $reservation, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        $reservation->setStatut(ReservationStatusEnum::TERMINEE);
        $entityManager->flush();

        return $responseFactory->success($responseFactory->reservation($reservation, true));
    }
}
