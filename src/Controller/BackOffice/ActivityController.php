<?php

namespace App\Controller\BackOffice;

use App\Entity\Activity;
use App\Form\BackOffice\ActivityType;
use App\Repository\ActivityRepository;
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function show(Activity $activity): Response
    {
        $activity->updateStatutIfNeeded();

        return $this->render('back_office/activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Activite modifiee avec succes.');

            return $this->redirectToRoute('app_back_activity_index');
        }

        return $this->render('back_office/activity/edit.html.twig', [
            'form' => $form->createView(),
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_activity_'.$activity->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($activity);
            $entityManager->flush();
            $this->addFlash('success', 'Activite supprimee avec succes.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_back_activity_index');
    }
}
