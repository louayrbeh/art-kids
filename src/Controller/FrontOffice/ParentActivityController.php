<?php

namespace App\Controller\FrontOffice;

use App\Entity\Activity;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\FrontOffice\ReservationType;
use App\Repository\ActivityRepository;
use App\Repository\ChildRepository;
use App\Service\RecommendationService;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent/activities', name: 'app_front_activity_')]
#[IsGranted('ROLE_PARENT')]
class ParentActivityController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository): Response
    {
        $activities = array_filter(
            $activityRepository->findOpenFutureActivities(),
            static fn (Activity $activity): bool => $activity->estDisponible()
        );

        return $this->render('front_office/activity/index.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Activity $activity, ChildRepository $childRepository, RecommendationService $recommendationService): Response
    {
        $activity->updateStatutIfNeeded();

        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParent($parent);
        $recommendations = [];
        foreach ($children as $child) {
            $recommendations[$child->getId()] = $recommendationService->recommendForChild($child);
        }

        return $this->render('front_office/activity/show.html.twig', [
            'activity' => $activity,
            'children' => $children,
            'recommendations' => $recommendations,
        ]);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['GET', 'POST'])]
    public function reserve(
        Request $request,
        Activity $activity,
        ChildRepository $childRepository,
        ReservationService $reservationService,
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParent($parent);
        if ([] === $children) {
            $this->addFlash('error', 'Ajoutez d\'abord un enfant avant de reserver une activite.');

            return $this->redirectToRoute('app_front_child_new');
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'children' => $children,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reservationService->createReservation($reservation->getChild(), $activity);
                $this->addFlash('success', 'Reservation enregistree avec succes.');

                return $this->redirectToRoute('app_front_reservation_index');
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('front_office/activity/show.html.twig', [
            'activity' => $activity,
            'children' => $children,
            'recommendations' => [],
            'reservationForm' => $form->createView(),
        ]);
    }
}
