<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\Api\ApiResponseFactory;
use App\Service\Api\ApiTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthApiController extends AbstractApiController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ApiTokenService $tokenService,
        ApiResponseFactory $responseFactory,
    ): Response {
        try {
            $payload = $this->payload($request);
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ('' === $email || '' === $password) {
            return $responseFactory->error('Email et mot de passe obligatoires.', Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User || !$user->isActive() || !$passwordHasher->isPasswordValid($user, $password)) {
            return $responseFactory->error('Identifiants invalides.', Response::HTTP_UNAUTHORIZED);
        }

        return $responseFactory->success([
            'token' => $tokenService->createToken($user),
            'tokenType' => 'Bearer',
            'expiresIn' => $tokenService->getTtlSeconds(),
            'user' => $responseFactory->user($user),
        ]);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success($responseFactory->user($this->currentApiUser()));
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->success(['message' => 'Token a supprimer cote client JavaFX.']);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        ApiResponseFactory $responseFactory,
    ): Response {
        try {
            $payload = $this->payload($request);
        } catch (\Throwable $exception) {
            return $this->badJson($exception, $responseFactory);
        }

        $password = (string) ($payload['password'] ?? '');
        if (strlen($password) < 6) {
            return $responseFactory->error('Le mot de passe doit contenir au moins 6 caracteres.', Response::HTTP_UNPROCESSABLE_ENTITY, [
                'password' => 'Mot de passe trop court.',
            ]);
        }

        $user = (new User())
            ->setNom((string) ($payload['nom'] ?? ''))
            ->setPrenom((string) ($payload['prenom'] ?? ''))
            ->setEmail((string) ($payload['email'] ?? ''))
            ->setTelephone(isset($payload['telephone']) ? (string) $payload['telephone'] : null)
            ->setRoles([UserRole::ROLE_PARENT->value])
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable());
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            return $responseFactory->validationError($violations);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $responseFactory->success($responseFactory->user($user), Response::HTTP_CREATED);
    }
}
