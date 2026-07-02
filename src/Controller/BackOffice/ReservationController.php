<?php

namespace App\Controller\BackOffice;

use App\Entity\Reservation;
use App\Enum\ActivityStatusEnum;
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
            'reservations' => $reservationRepository->findLatest(50),
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
    public function confirm(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
    ): Response
    {
        if ($this->isCsrfTokenValid('confirm_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $activity = $reservation->getActivity();

            if (null === $activity) {
                $this->addFlash('error', 'Reservation invalide.');

                return $this->redirectToRoute('app_back_reservation_index');
            }

            $activity->updateStatutIfNeeded();

            if ($reservation->estAnnulee()) {
                $this->addFlash('error', 'Une reservation annulee ne peut pas etre confirmee.');

                return $this->redirectToRoute('app_back_reservation_index');
            }

            if (
                !$activity->estFuture()
                || ActivityStatusEnum::ANNULEE === $activity->getStatut()
                || ActivityStatusEnum::TERMINEE === $activity->getStatut()
            ) {
                $this->addFlash('error', 'Cette activite n est plus disponible pour confirmation.');

                return $this->redirectToRoute('app_back_reservation_index');
            }

            if ($reservationRepository->countActiveForActivity($activity) > (int) $activity->getCapaciteMax()) {
                $this->addFlash('error', 'La capacite maximale de cette activite est deja depassee.');

                return $this->redirectToRoute('app_back_reservation_index');
            }

            if ($reservation->estConfirmee()) {
                $this->addFlash('info', 'Cette reservation est deja confirmee.');

                return $this->redirectToRoute('app_back_reservation_index');
            }

            $reservation->confirmer();
            $activity->updateStatutIfNeeded();
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
            if ($reservation->estAnnulee()) {
                $this->addFlash('info', 'Cette reservation est deja annulee.');

                return $this->redirectToRoute('app_back_reservation_index');
            }

            $reservation->annuler();
            $reservation->getActivity()?->updateStatutIfNeeded();
            $entityManager->flush();
            $this->addFlash('success', 'Reservation annulee.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_back_reservation_index');
    }
}
