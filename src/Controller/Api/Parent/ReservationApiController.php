<?php

namespace App\Controller\Api\Parent;

use App\Controller\Api\AbstractApiController;
use App\Entity\Reservation;
use App\Repository\ActivityRepository;
use App\Repository\ChildRepository;
use App\Repository\ReservationRepository;
use App\Service\Api\ApiResponseFactory;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/parent/reservations', name: 'api_parent_reservations_')]
class ReservationApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, ApiResponseFactory $responseFactory): Response
    {
        $reservations = array_map(
            fn (Reservation $reservation): array => $responseFactory->reservation($reservation),
            $reservationRepository->findByParent($this->currentApiUser())
        );

        return $responseFactory->success($reservations);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ChildRepository $childRepository,
        ActivityRepository $activityRepository,
        ReservationService $reservationService,
        EntityManagerInterface $entityManager,
        ApiResponseFactory $responseFactory,
    ): Response {
        try {
            $payload = $this->payload($request);
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $errors = [];
        if (!isset($payload['childId']) || '' === (string) $payload['childId']) {
            $errors['childId'] = 'Enfant obligatoire.';
        }
        if (!isset($payload['activityId']) || '' === (string) $payload['activityId']) {
            $errors['activityId'] = 'Activite obligatoire.';
        }
        if ([] !== $errors) {
            return $responseFactory->error('Donnees invalides.', Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $child = $childRepository->find((int) $payload['childId']);
        $activity = $activityRepository->find((int) $payload['activityId']);

        if (null === $child || $child->getParent() !== $this->currentApiUser()) {
            return $responseFactory->error('Enfant introuvable ou non autorise.', Response::HTTP_FORBIDDEN);
        }
        if (null === $activity) {
            return $responseFactory->error('Activite introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $reservation = $reservationService->createReservation($child, $activity);
            $entityManager->refresh($reservation);
        } catch (\Throwable $exception) {
            return $responseFactory->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $responseFactory->success($responseFactory->reservation($reservation), Response::HTTP_CREATED);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['PUT'])]
    public function cancel(Reservation $reservation, ReservationService $reservationService, ApiResponseFactory $responseFactory): Response
    {
        if ($reservation->getChild()?->getParent() !== $this->currentApiUser()) {
            return $responseFactory->error('Reservation introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $reservationService->cancelReservation($reservation);
        } catch (\Throwable $exception) {
            return $responseFactory->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $responseFactory->success($responseFactory->reservation($reservation));
    }
}
