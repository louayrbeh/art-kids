<?php

namespace App\Controller\BackOffice;

use App\Entity\Activity;
use App\Enum\ActivityStatusEnum;
use App\Form\BackOffice\ActivityType;
use App\Repository\ActivityRepository;
use App\Service\ImageUploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activities', name: 'app_back_activity_')]
#[IsGranted('ROLE_ADMIN')]
class ActivityController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository): Response
    {
        return $this->render('back_office/activity/index.html.twig', [
            'activities' => $activityRepository->findBy([], ['dateActivite' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageUploaderService $imageUploaderService,
    ): Response {
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if (null !== $imageFile) {
                try {
                    $activity->setImage($imageUploaderService->uploadActivityImage($imageFile));
                } catch (\RuntimeException $exception) {
                    $this->addFlash('error', $exception->getMessage());

                    return $this->render('back_office/activity/new.html.twig', [
                        'form' => $form->createView(),
                        'activity' => $activity,
                    ]);
                }
            }

            $activity->setCreatedAt(new \DateTimeImmutable());
            if (null === $activity->getStatut()) {
                $activity->setStatut(ActivityStatusEnum::OUVERTE);
            }
            $activity->updateStatutIfNeeded();
            $entityManager->persist($activity);
            $entityManager->flush();
            $this->addFlash('success', 'Activite creee avec succes.');

            return $this->redirectToRoute('app_back_activity_index');
        }

        return $this->render('back_office/activity/new.html.twig', [
            'form' => $form->createView(),
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $previousStatus = $activity->getStatut();
        $activity->updateStatutIfNeeded();
        if ($previousStatus !== $activity->getStatut()) {
            $entityManager->flush();
        }

        return $this->render('back_office/activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Activity $activity,
        EntityManagerInterface $entityManager,
        ImageUploaderService $imageUploaderService,
    ): Response {
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldImage = $activity->getImage();
            $imageFile = $form->get('imageFile')->getData();

            if (null !== $imageFile) {
                try {
                    $newFilename = $imageUploaderService->uploadActivityImage($imageFile);
                } catch (\RuntimeException $exception) {
                    $this->addFlash('error', $exception->getMessage());

                    return $this->render('back_office/activity/edit.html.twig', [
                        'form' => $form->createView(),
                        'activity' => $activity,
                    ]);
                }

                $activity->setImage($newFilename);
            }

            $activity->setUpdatedAt(new \DateTimeImmutable());
            $activity->updateStatutIfNeeded();
            $entityManager->flush();

            if (null !== $imageFile) {
                $imageUploaderService->deleteActivityImage($oldImage);
            }

            $this->addFlash('success', 'Activite modifiee avec succes.');

            return $this->redirectToRoute('app_back_activity_index');
        }

        return $this->render('back_office/activity/edit.html.twig', [
            'form' => $form->createView(),
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Activity $activity,
        EntityManagerInterface $entityManager,
        ActivityRepository $activityRepository,
        ImageUploaderService $imageUploaderService,
    ): Response {
        if ($this->isCsrfTokenValid('delete_activity_'.$activity->getId(), (string) $request->request->get('_token'))) {
            if ($activityRepository->hasActiveReservations($activity)) {
                $this->addFlash('error', 'Impossible de supprimer une activite avec des reservations actives.');

                return $this->redirectToRoute('app_back_activity_index');
            }

            $imageFilename = $activity->getImage();
            $entityManager->remove($activity);
            $entityManager->flush();
            $imageUploaderService->deleteActivityImage($imageFilename);
            $this->addFlash('success', 'Activite supprimee avec succes.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_back_activity_index');
    }
}
