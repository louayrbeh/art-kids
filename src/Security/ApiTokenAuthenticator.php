<?php

namespace App\Security;

use App\Repository\UserRepository;
use App\Service\Api\ApiTokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ApiTokenService $apiTokenService,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api')
            && $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = (string) $request->headers->get('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new AuthenticationException('Header Authorization Bearer manquant ou invalide.');
        }

        try {
            $payload = $this->apiTokenService->parseToken($matches[1]);
        } catch (\Throwable $exception) {
            throw new AuthenticationException($exception->getMessage());
        }

        return new SelfValidatingPassport(new UserBadge((string) $payload['email'], function (string $email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (null === $user || !$user->isActive()) {
                throw new AuthenticationException('Utilisateur API introuvable ou inactif.');
            }

            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->start($request, $exception);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => $authException?->getMessage() ?: 'Authentification API requise.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
