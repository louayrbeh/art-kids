<?php

namespace App\Controller\Api\Parent;

use App\Controller\Api\AbstractApiController;
use App\Entity\Child;
use App\Enum\SexeEnum;
use App\Repository\ChildRepository;
use App\Service\Api\ApiResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/parent/children', name: 'api_parent_children_')]
class ChildApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ChildRepository $childRepository, ApiResponseFactory $responseFactory): Response
    {
        $children = array_map(
            fn (Child $child): array => $responseFactory->child($child),
            $childRepository->findByParent($this->currentApiUser())
        );

        return $responseFactory->success($children);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ApiResponseFactory $responseFactory,
    ): Response {
        try {
            $payload = $this->payload($request);
            $child = $this->hydrateChild(new Child(), $payload);
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $child->setParent($this->currentApiUser());
        $violations = $validator->validate($child);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->persist($child);
        $entityManager->flush();

        return $responseFactory->success($responseFactory->child($child), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Child $child, ApiResponseFactory $responseFactory): Response
    {
        if ($child->getParent() !== $this->currentApiUser()) {
            return $responseFactory->error('Enfant introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $responseFactory->success($responseFactory->child($child));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Request $request,
        Child $child,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ApiResponseFactory $responseFactory,
    ): Response {
        if ($child->getParent() !== $this->currentApiUser()) {
            return $responseFactory->error('Enfant introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->hydrateChild($child, $this->payload($request));
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($child);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->flush();

        return $responseFactory->success($responseFactory->child($child));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Child $child, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        if ($child->getParent() !== $this->currentApiUser()) {
            return $responseFactory->error('Enfant introuvable.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($child);
        $entityManager->flush();

        return $responseFactory->success(['deleted' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateChild(Child $child, array $payload): Child
    {
        if (array_key_exists('nom', $payload)) {
            $child->setNom((string) $payload['nom']);
        }
        if (array_key_exists('prenom', $payload)) {
            $child->setPrenom((string) $payload['prenom']);
        }
        if (array_key_exists('dateNaissance', $payload)) {
            $child->setDateNaissance(new \DateTimeImmutable((string) $payload['dateNaissance']));
        }
        if (array_key_exists('sexe', $payload)) {
            $sexe = SexeEnum::tryFrom((string) $payload['sexe']);
            if (null === $sexe) {
                throw new \InvalidArgumentException('Sexe invalide. Valeurs: GARCON, FILLE.');
            }
            $child->setSexe($sexe);
        }

        return $child;
    }
}
