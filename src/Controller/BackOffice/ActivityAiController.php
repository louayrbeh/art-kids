<?php

namespace App\Controller\BackOffice;

use App\Service\ActivityAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activities/generate-description', name: 'app_back_office_activity_generate_description', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class ActivityAiController extends AbstractController
{
    public function __invoke(Request $request, ActivityAiService $activityAiService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('generate_activity_description', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json([
                'success' => false,
                'message' => 'Token CSRF invalide.',
            ], 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json([
                'success' => false,
                'message' => 'Requete invalide.',
            ], 400);
        }

        $title = trim((string) ($payload['title'] ?? ''));
        if ('' === $title) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez saisir un titre avant de generer la description.',
            ], 422);
        }

        if (!$this->isNullableInteger($payload['ageMin'] ?? null) || !$this->isNullableInteger($payload['ageMax'] ?? null)) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez verifier les ages avant de generer la description.',
            ], 422);
        }

        $ageMin = $this->toNullableInteger($payload['ageMin'] ?? null);
        $ageMax = $this->toNullableInteger($payload['ageMax'] ?? null);

        if (null !== $ageMin && null !== $ageMax && $ageMax < $ageMin) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez verifier les ages avant de generer la description.',
            ], 422);
        }

        $categoryName = trim((string) ($payload['category'] ?? ''));

        try {
            $description = $activityAiService->generateDescription(
                $title,
                '' !== $categoryName ? $categoryName : null,
                $ageMin,
                $ageMax,
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de generer la description. Veuillez reessayer ou saisir la description manuellement.',
            ]);
        }

        return $this->json([
            'success' => true,
            'description' => $description,
        ]);
    }

    private function isNullableInteger(mixed $value): bool
    {
        if (null === $value || '' === $value) {
            return true;
        }

        return false !== filter_var($value, FILTER_VALIDATE_INT);
    }

    private function toNullableInteger(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (int) $value;
    }
}
