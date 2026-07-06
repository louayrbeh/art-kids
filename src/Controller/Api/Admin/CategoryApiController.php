<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiController;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\Api\ApiResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/categories', name: 'api_admin_categories_')]
class CategoryApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success(array_map(
            fn (Category $category): array => $responseFactory->category($category),
            $categoryRepository->findBy([], ['nom' => 'ASC'])
        ));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, ApiResponseFactory $responseFactory): Response
    {
        try {
            $category = $this->hydrate(new Category(), $this->payload($request));
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($category);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->persist($category);
        $entityManager->flush();

        return $responseFactory->success($responseFactory->category($category), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Category $category, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success($responseFactory->category($category));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Category $category, EntityManagerInterface $entityManager, ValidatorInterface $validator, ApiResponseFactory $responseFactory): Response
    {
        try {
            $this->hydrate($category, $this->payload($request));
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($category);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->flush();

        return $responseFactory->success($responseFactory->category($category));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Category $category, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        if ($categoryRepository->hasActivities($category)) {
            return $responseFactory->error('Impossible de supprimer une categorie qui contient encore des activites.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return $responseFactory->success(['deleted' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrate(Category $category, array $payload): Category
    {
        if (array_key_exists('nom', $payload)) {
            $category->setNom((string) $payload['nom']);
        }
        if (array_key_exists('description', $payload)) {
            $category->setDescription(isset($payload['description']) ? (string) $payload['description'] : null);
        }
        if (array_key_exists('image', $payload)) {
            $category->setImage(isset($payload['image']) ? (string) $payload['image'] : null);
        }

        return $category;
    }
}
