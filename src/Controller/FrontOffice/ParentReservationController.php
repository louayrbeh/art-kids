<?php

namespace App\Controller\FrontOffice;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent/reservations', name: 'app_front_reservation_')]
#[IsGranted('ROLE_PARENT')]
class ParentReservationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();

        return $this->render('front_office/reservation/index.html.twig', [
            'reservations' => $reservationRepository->findByParent($parent),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        $this->denyUnlessOwnReservation($reservation);

        return $this->render('front_office/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, ReservationService $reservationService): Response
    {
        $this->denyUnlessOwnReservation($reservation);

        if (!$this->isCsrfTokenValid('cancel_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_front_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $reservationService->cancelReservation($reservation);
            $this->addFlash('success', 'Reservation annulee avec succes.');
        } catch (\Throwable $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_front_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyUnlessOwnReservation(Reservation $reservation): void
    {
        /** @var User $parent */
        $parent = $this->getUser();
        if ($reservation->getChild()?->getParent() !== $parent) {
            throw $this->createAccessDeniedException('Acces refuse a cette reservation.');
        }
    }
}
