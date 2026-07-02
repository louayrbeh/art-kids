<?php

namespace App\Controller\FrontOffice;

use App\Entity\User;
use App\Repository\ChildRepository;
use App\Service\ParentStatisticService;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent')]
#[IsGranted('ROLE_PARENT')]
class ParentController extends AbstractController
{
    #[Route('', name: 'app_front_parent_index', methods: ['GET'])]
    #[Route('', name: 'app_front_office_parent_dashboard', methods: ['GET'])]
    public function index(
        ParentStatisticService $parentStatisticService,
        ChildRepository $childRepository,
        RecommendationService $recommendationService,
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParentWithRelations($parent);

        return $this->render('front_office/parent/dashboard.html.twig', [
            'parent' => $parent,
            'children' => $children,
            'dashboard' => $parentStatisticService->getDashboardData($parent),
            'recommendations' => $recommendationService->recommendForParent($parent),
            'today' => new \DateTimeImmutable(),
        ]);
    }
}
