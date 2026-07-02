<?php

namespace App\Controller\FrontOffice;

use App\Entity\Activity;
use App\Entity\Category;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\FrontOffice\ReservationType;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
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
    public function index(
        Request $request,
        ActivityRepository $activityRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $age = $request->query->getInt('age');
        $category = null;
        $categoryId = $request->query->get('category');
        if (is_numeric($categoryId)) {
            $category = $categoryRepository->find((int) $categoryId);
        }

        return $this->render('front_office/activity/index.html.twig', [
            'activities' => $activityRepository->findAvailableForParent($search, $category, $age > 0 ? $age : null),
            'categories' => $categoryRepository->findBy([], ['nom' => 'ASC']),
            'filters' => [
                'q' => $search,
                'category' => $category?->getId(),
                'age' => $age > 0 ? $age : null,
            ],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Activity $activity,
        ChildRepository $childRepository,
        RecommendationService $recommendationService,
    ): Response {
        if (!$activity->estFuture() || null === $activity->getCategory()) {
            $this->addFlash('error', 'Cette activite n est plus disponible.');

            return $this->redirectToRoute('app_front_activity_index');
        }

        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParent($parent);
        $compatibleChildren = array_values(array_filter(
            $children,
            static fn ($child): bool => $child->getAge() >= $activity->getAgeMin() && $child->getAge() <= $activity->getAgeMax()
        ));

        $reservationForm = null;
        if ([] !== $compatibleChildren && $activity->estDisponible()) {
            $reservationForm = $this->createForm(ReservationType::class, new Reservation(), [
                'children' => $compatibleChildren,
                'action' => $this->generateUrl('app_front_activity_reserve', ['id' => $activity->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        $recommendations = [];
        foreach ($compatibleChildren as $child) {
            $recommendations[$child->getId()] = array_values(array_filter(
                $recommendationService->recommendForChild($child),
                static fn (Activity $recommendedActivity): bool => $recommendedActivity->getId() !== $activity->getId()
            ));
        }

        return $this->render('front_office/activity/show.html.twig', [
            'activity' => $activity,
            'children' => $children,
            'compatibleChildren' => $compatibleChildren,
            'recommendations' => $recommendations,
            'reservationForm' => $reservationForm,
        ]);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['GET', 'POST'])]
    public function reserve(
        Request $request,
        Activity $activity,
        ChildRepository $childRepository,
        ReservationService $reservationService,
        RecommendationService $recommendationService,
    ): Response {
        if ('GET' === $request->getMethod()) {
            return $this->redirectToRoute('app_front_activity_show', ['id' => $activity->getId()]);
        }

        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParent($parent);
        $compatibleChildren = array_values(array_filter(
            $children,
            static fn ($child): bool => $child->getAge() >= $activity->getAgeMin() && $child->getAge() <= $activity->getAgeMax()
        ));

        if ([] === $children) {
            $this->addFlash('error', 'Ajoutez d abord un enfant avant de reserver une activite.');

            return $this->redirectToRoute('app_front_child_new');
        }

        if ([] === $compatibleChildren) {
            $this->addFlash('error', 'Aucun de vos enfants n est compatible avec cette activite.');

            return $this->redirectToRoute('app_front_activity_show', ['id' => $activity->getId()]);
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'children' => $compatibleChildren,
            'action' => $this->generateUrl('app_front_activity_reserve', ['id' => $activity->getId()]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reservationService->assertReservationIsAllowed($reservation->getChild(), $activity, $parent);
                $reservationService->createReservation($reservation->getChild(), $activity);
                $this->addFlash('success', 'Reservation effectuee avec succes.');

                return $this->redirectToRoute('app_front_reservation_index');
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        $recommendations = [];
        foreach ($compatibleChildren as $child) {
            $recommendations[$child->getId()] = array_values(array_filter(
                $recommendationService->recommendForChild($child),
                static fn (Activity $recommendedActivity): bool => $recommendedActivity->getId() !== $activity->getId()
            ));
        }

        return $this->render('front_office/activity/show.html.twig', [
            'activity' => $activity,
            'children' => $children,
            'compatibleChildren' => $compatibleChildren,
            'recommendations' => $recommendations,
            'reservationForm' => $form->createView(),
        ]);
    }
}
