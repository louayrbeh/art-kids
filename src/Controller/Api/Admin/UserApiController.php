<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiController;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\Api\ApiResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/users', name: 'api_admin_users_')]
class UserApiController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success(array_map(
            fn (User $user): array => $responseFactory->user($user),
            $userRepository->findBy([], ['createdAt' => 'DESC'])
        ));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, ApiResponseFactory $responseFactory): Response
    {
        try {
            $payload = $this->payload($request);
            $password = (string) ($payload['password'] ?? '');
            if (strlen($password) < 6) {
                return $responseFactory->error('Le mot de passe doit contenir au moins 6 caracteres.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $user = $this->hydrate(new User(), $payload);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $responseFactory->success($responseFactory->user($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success($responseFactory->user($user));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, ValidatorInterface $validator, ApiResponseFactory $responseFactory): Response
    {
        try {
            $payload = $this->payload($request);
            $selectedRoles = $this->normalizeRoles($payload['roles'] ?? $user->getRoles());
            $isActive = array_key_exists('isActive', $payload) ? (bool) $payload['isActive'] : $user->isActive();

            if ($this->isCurrentUser($user) && !in_array(UserRole::ROLE_ADMIN->value, $selectedRoles, true)) {
                return $responseFactory->error('Vous ne pouvez pas retirer votre propre role administrateur.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ($this->isCurrentUser($user) && !$isActive) {
                return $responseFactory->error('Vous ne pouvez pas desactiver votre propre compte.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ($user->isAdmin() && !in_array(UserRole::ROLE_ADMIN->value, $selectedRoles, true) && $userRepository->countAdmins() <= 1) {
                return $responseFactory->error('Impossible de retirer le role du dernier administrateur.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ($user->isAdmin() && $user->isActive() && (!$isActive || !in_array(UserRole::ROLE_ADMIN->value, $selectedRoles, true)) && $userRepository->countActiveAdmins() <= 1) {
                return $responseFactory->error('Impossible de desactiver le dernier administrateur actif.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->hydrate($user, $payload);
            if (isset($payload['password']) && '' !== (string) $payload['password']) {
                $user->setPassword($passwordHasher->hashPassword($user, (string) $payload['password']));
            }
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->flush();

        return $responseFactory->success($responseFactory->user($user));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(User $user, UserRepository $userRepository, EntityManagerInterface $entityManager, ApiResponseFactory $responseFactory): Response
    {
        if ($this->isCurrentUser($user)) {
            return $responseFactory->error('Vous ne pouvez pas supprimer votre propre compte.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($user->isAdmin() && $userRepository->countAdmins() <= 1) {
            return $responseFactory->error('Impossible de supprimer le dernier administrateur.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($user->getChildren()->count() > 0) {
            $user->setIsActive(false);
            $entityManager->flush();

            return $responseFactory->success(['disabled' => true, 'user' => $responseFactory->user($user)]);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $responseFactory->success(['deleted' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrate(User $user, array $payload): User
    {
        if (array_key_exists('nom', $payload)) {
            $user->setNom((string) $payload['nom']);
        }
        if (array_key_exists('prenom', $payload)) {
            $user->setPrenom((string) $payload['prenom']);
        }
        if (array_key_exists('email', $payload)) {
            $user->setEmail((string) $payload['email']);
        }
        if (array_key_exists('telephone', $payload)) {
            $user->setTelephone(isset($payload['telephone']) ? (string) $payload['telephone'] : null);
        }
        if (array_key_exists('roles', $payload)) {
            $user->setRoles($this->normalizeRoles($payload['roles']));
        }
        if (array_key_exists('isActive', $payload)) {
            $user->setIsActive((bool) $payload['isActive']);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function normalizeRoles(mixed $roles): array
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $allowed = [UserRole::ROLE_PARENT->value, UserRole::ROLE_ADMIN->value];
        $normalized = array_values(array_intersect(array_map('strval', $roles), $allowed));

        return [] === $normalized ? [UserRole::ROLE_PARENT->value] : $normalized;
    }

    private function isCurrentUser(User $user): bool
    {
        return $this->currentApiUser()->getId() === $user->getId();
    }
}
