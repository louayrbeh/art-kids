<?php

namespace App\Controller\FrontOffice;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnReservation($reservation);

        if ($this->isCsrfTokenValid('cancel_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->annuler();
            $entityManager->flush();
            $this->addFlash('success', 'Reservation annulee.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_front_reservation_index');
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
