<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiController;
use App\Entity\Activity;
use App\Enum\ActivityStatusEnum;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
use App\Service\Api\ApiResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/activities', name: 'api_admin_activities_')]
class ActivityApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success(array_map(
            fn (Activity $activity): array => $responseFactory->activity($activity),
            $activityRepository->findBy([], ['dateActivite' => 'ASC'])
        ));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, ApiResponseFactory $responseFactory): Response
    {
        try {
            $activity = $this->hydrate(new Activity(), $this->payload($request), $categoryRepository);
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($activity);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $activity->updateStatutIfNeeded();
        $entityManager->persist($activity);
        $entityManager->flush();

        return $responseFactory->success($responseFactory->activity($activity), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Activity $activity, ApiResponseFactory $responseFactory): Response
    {
        $activity->updateStatutIfNeeded();

        return $responseFactory->success($responseFactory->activity($activity));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Activity $activity, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, ApiResponseFactory $responseFactory): Response
    {
        try {
            $this->hydrate($activity, $this->payload($request), $categoryRepository);
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($activity);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $activity->updateStatutIfNeeded();
        $entityManager->flush();

        return $responseFactory->success($responseFactory->activity($activity));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Activity $activity, ActivityRepository $activityRepository, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        if ($activityRepository->hasActiveReservations($activity)) {
            return $responseFactory->error('Impossible de supprimer une activite avec des reservations actives.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->remove($activity);
        $entityManager->flush();

        return $responseFactory->success(['deleted' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrate(Activity $activity, array $payload, CategoryRepository $categoryRepository): Activity
    {
        if (array_key_exists('titre', $payload)) {
            $activity->setTitre((string) $payload['titre']);
        }
        if (array_key_exists('description', $payload)) {
            $activity->setDescription((string) $payload['description']);
        }
        if (array_key_exists('image', $payload)) {
            $activity->setImage(isset($payload['image']) ? (string) $payload['image'] : null);
        }
        if (array_key_exists('dateActivite', $payload)) {
            $activity->setDateActivite(new \DateTimeImmutable((string) $payload['dateActivite']));
        }
        if (array_key_exists('heureDebut', $payload)) {
            $activity->setHeureDebut(new \DateTimeImmutable((string) $payload['heureDebut']));
        }
        if (array_key_exists('heureFin', $payload)) {
            $activity->setHeureFin(new \DateTimeImmutable((string) $payload['heureFin']));
        }
        if (array_key_exists('capaciteMax', $payload)) {
            $activity->setCapaciteMax((int) $payload['capaciteMax']);
        }
        if (array_key_exists('ageMin', $payload)) {
            $activity->setAgeMin((int) $payload['ageMin']);
        }
        if (array_key_exists('ageMax', $payload)) {
            $activity->setAgeMax((int) $payload['ageMax']);
        }
        if (array_key_exists('prix', $payload)) {
            $activity->setPrix(null === $payload['prix'] ? null : (string) $payload['prix']);
        }
        if (array_key_exists('statut', $payload)) {
            $status = ActivityStatusEnum::tryFrom((string) $payload['statut']);
            if (null === $status) {
                throw new \InvalidArgumentException('Statut activite invalide.');
            }
            $activity->setStatut($status);
        }
        if (array_key_exists('lieu', $payload)) {
            $activity->setLieu(isset($payload['lieu']) ? (string) $payload['lieu'] : null);
        }
        if (array_key_exists('categoryId', $payload)) {
            $category = $categoryRepository->find((int) $payload['categoryId']);
            if (null === $category) {
                throw new \InvalidArgumentException('Categorie introuvable.');
            }
            $activity->setCategory($category);
        }

        return $activity;
    }
}
