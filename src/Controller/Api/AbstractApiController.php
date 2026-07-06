<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Api\ApiResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    protected function payload(Request $request): array
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return [];
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \InvalidArgumentException('JSON invalide.');
        }

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Le corps JSON doit etre un objet.');
        }

        return $payload;
    }

    protected function currentApiUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur API non authentifie.');
        }

        return $user;
    }

    protected function badJson(\Throwable $exception, ApiResponseFactory $responseFactory): Response
    {
        return $responseFactory->error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
    }
}
