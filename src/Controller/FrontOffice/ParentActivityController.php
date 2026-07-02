<?php

namespace App\Controller\FrontOffice;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\User;
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

            return $this->redirectToRoute('app_front_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParent($parent);
        $compatibleChildren = $this->getCompatibleChildren($children, $activity);

        return $this->render('front_office/activity/show.html.twig', [
            'activity' => $activity,
            'children' => $children,
            'compatibleChildren' => $compatibleChildren,
            'recommendations' => $this->buildRecommendations($compatibleChildren, $activity, $recommendationService),
        ]);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['POST'])]
    public function reserve(
        Request $request,
        Activity $activity,
        ChildRepository $childRepository,
        ReservationService $reservationService,
    ): Response {
        if (!$this->isCsrfTokenValid('reserve_activity_'.$activity->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_front_activity_show', [
                'id' => $activity->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParent($parent);
        if ([] === $children) {
            $this->addFlash('danger', 'Ajoutez d abord un enfant avant de reserver une activite.');

            return $this->redirectToRoute('app_front_child_new', [], Response::HTTP_SEE_OTHER);
        }

        $childId = $request->request->get('child_id');
        $child = is_numeric($childId) ? $childRepository->find((int) $childId) : null;

        if (!$child instanceof Child || $child->getParent() !== $parent) {
            $this->addFlash('danger', 'Enfant invalide.');

            return $this->redirectToRoute('app_front_activity_show', [
                'id' => $activity->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        try {
            $reservationService->assertReservationIsAllowed($child, $activity, $parent);
            $reservationService->createReservation($child, $activity);
            $this->addFlash('success', 'Reservation effectuee avec succes.');

            return $this->redirectToRoute('app_front_reservation_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', $exception->getMessage() ?: 'Impossible de creer la reservation.');

            return $this->redirectToRoute('app_front_activity_show', [
                'id' => $activity->getId(),
            ], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @param list<Child> $children
     *
     * @return list<Child>
     */
    private function getCompatibleChildren(array $children, Activity $activity): array
    {
        return array_values(array_filter(
            $children,
            static fn (Child $child): bool => $child->getAge() >= $activity->getAgeMin() && $child->getAge() <= $activity->getAgeMax()
        ));
    }

    /**
     * @param list<Child> $compatibleChildren
     *
     * @return array<int, list<Activity>>
     */
    private function buildRecommendations(
        array $compatibleChildren,
        Activity $activity,
        RecommendationService $recommendationService,
    ): array {
        $recommendations = [];
        foreach ($compatibleChildren as $child) {
            $recommendations[$child->getId()] = array_values(array_filter(
                $recommendationService->recommendForChild($child),
                static fn (Activity $recommendedActivity): bool => $recommendedActivity->getId() !== $activity->getId()
            ));
        }

        return $recommendations;
    }
}
