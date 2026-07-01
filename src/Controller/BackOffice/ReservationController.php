<?php

namespace App\Controller\BackOffice;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations', name: 'app_back_reservation_')]
#[IsGranted('ROLE_ADMIN')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('back_office/reservation/index.html.twig', [
            'reservations' => $reservationRepository->findBy([], ['dateReservation' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('back_office/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('confirm_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->confirmer();
            $entityManager->flush();
            $this->addFlash('success', 'Reservation confirmee.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_back_reservation_index');
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('cancel_admin_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->annuler();
            $entityManager->flush();
            $this->addFlash('success', 'Reservation annulee.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_back_reservation_index');
    }
}
