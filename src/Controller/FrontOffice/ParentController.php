<?php

namespace App\Controller\FrontOffice;

use App\Entity\User;
use App\Repository\ChildRepository;
use App\Service\AiRecommendationService;
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
        AiRecommendationService $aiRecommendationService,
    ): Response {
        /** @var User $parent */
        $parent = $this->getUser();
        $children = $childRepository->findByParentWithRelations($parent);
        $recommendationsByChild = $recommendationService->recommendForParent($parent, 4);
        $remainingAiExplanations = 10;

        foreach ($recommendationsByChild as &$group) {
            foreach ($group['recommendations'] as &$item) {
                if ($remainingAiExplanations > 0) {
                    $item['reason'] = $aiRecommendationService->explainRecommendation($group['child'], $item['activity']);
                    --$remainingAiExplanations;
                }
            }
        }
        unset($group, $item);

        return $this->render('front_office/parent/dashboard.html.twig', [
            'parent' => $parent,
            'children' => $children,
            'dashboard' => $parentStatisticService->getDashboardData($parent),
            'recommendationsByChild' => $recommendationsByChild,
            'today' => new \DateTimeImmutable(),
        ]);
    }
}
