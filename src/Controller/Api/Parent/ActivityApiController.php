<?php

namespace App\Controller\Api\Parent;

use App\Controller\Api\AbstractApiController;
use App\Entity\Activity;
use App\Repository\ActivityRepository;
use App\Service\Api\ApiResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/parent/activities', name: 'api_parent_activities_')]
class ActivityApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository, ApiResponseFactory $responseFactory): Response
    {
        $activities = array_map(
            fn (Activity $activity): array => $responseFactory->activity($activity),
            $activityRepository->findAvailableForParent()
        );

        return $responseFactory->success($activities);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Activity $activity, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success($responseFactory->activity($activity));
    }
}
