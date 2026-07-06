<?php

namespace App\Service\Api;

use App\Entity\Activity;
use App\Entity\Category;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ApiResponseFactory
{
    public function success(mixed $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * @param array<string, mixed> $errors
     */
    public function error(string $message, int $status = Response::HTTP_BAD_REQUEST, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ([] !== $errors) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $status);
    }

    public function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath() ?: 'global';
            $errors[$path] = $violation->getMessage();
        }

        return $this->error('Donnees invalides.', Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function user(User $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $this->formatDateTime($user->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($user->getUpdatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function child(Child $child, bool $includeParent = false): array
    {
        $data = [
            'id' => $child->getId(),
            'nom' => $child->getNom(),
            'prenom' => $child->getPrenom(),
            'fullName' => $child->getFullName(),
            'dateNaissance' => $this->formatDate($child->getDateNaissance()),
            'age' => $child->getAge(),
            'sexe' => $child->getSexe()?->value,
        ];

        if ($includeParent && null !== $child->getParent()) {
            $data['parent'] = $this->user($child->getParent());
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function category(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'nom' => $category->getNom(),
            'description' => $category->getDescription(),
            'image' => $category->getImage(),
            'imageUrl' => $this->categoryImageUrl($category->getImage()),
            'createdAt' => $this->formatDateTime($category->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($category->getUpdatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activity(Activity $activity): array
    {
        $category = $activity->getCategory();

        return [
            'id' => $activity->getId(),
            'titre' => $activity->getTitre(),
            'description' => $activity->getDescription(),
            'image' => $activity->getImage(),
            'imageUrl' => $this->activityImageUrl($activity->getImage()),
            'dateActivite' => $this->formatDate($activity->getDateActivite()),
            'heureDebut' => $this->formatTime($activity->getHeureDebut()),
            'heureFin' => $this->formatTime($activity->getHeureFin()),
            'capaciteMax' => $activity->getCapaciteMax(),
            'placesDisponibles' => $activity->placesDisponibles(),
            'ageMin' => $activity->getAgeMin(),
            'ageMax' => $activity->getAgeMax(),
            'prix' => $activity->getPrix(),
            'statut' => $activity->getStatut()?->value,
            'lieu' => $activity->getLieu(),
            'category' => null === $category ? null : [
                'id' => $category->getId(),
                'nom' => $category->getNom(),
            ],
            'createdAt' => $this->formatDateTime($activity->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($activity->getUpdatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reservation(Reservation $reservation, bool $includeParent = false): array
    {
        $child = $reservation->getChild();
        $activity = $reservation->getActivity();

        return [
            'id' => $reservation->getId(),
            'dateReservation' => $this->formatDateTime($reservation->getDateReservation()),
            'statut' => $reservation->getStatut()?->value,
            'child' => null === $child ? null : $this->child($child, $includeParent),
            'activity' => null === $activity ? null : $this->activity($activity),
            'createdAt' => $this->formatDateTime($reservation->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($reservation->getUpdatedAt()),
        ];
    }

    public function activityImageUrl(?string $image): string
    {
        return $this->imageUrl($image, '/uploads/activities/', '/assets/images/activity-placeholder.svg');
    }

    public function categoryImageUrl(?string $image): string
    {
        return $this->imageUrl($image, '/uploads/categories/', '/assets/images/category-placeholder.svg');
    }

    private function imageUrl(?string $image, string $localPrefix, string $placeholder): string
    {
        if (null === $image || '' === trim($image)) {
            return $placeholder;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return $localPrefix.$image;
    }

    private function formatDate(?\DateTimeInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    private function formatTime(?\DateTimeInterface $time): ?string
    {
        return $time?->format('H:i');
    }

    private function formatDateTime(?\DateTimeInterface $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
