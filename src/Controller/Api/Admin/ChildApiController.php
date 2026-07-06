<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiController;
use App\Entity\Child;
use App\Repository\ChildRepository;
use App\Service\Api\ApiResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/children', name: 'api_admin_children_')]
class ChildApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ChildRepository $childRepository, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success(array_map(
            fn (Child $child): array => $responseFactory->child($child, true),
            $childRepository->findAllWithParents()
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Child $child, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success($responseFactory->child($child, true));
    }
}
