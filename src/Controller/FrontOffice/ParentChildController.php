<?php

namespace App\Controller\FrontOffice;

use App\Entity\Child;
use App\Entity\User;
use App\Form\FrontOffice\ChildType;
use App\Repository\ChildRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent/children', name: 'app_front_child_')]
#[IsGranted('ROLE_PARENT')]
class ParentChildController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ChildRepository $childRepository): Response
    {
        return $this->render('front_office/child/index.html.twig', [
            'children' => $childRepository->findByParentWithRelations($this->getParentUser()),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $child = new Child();
        $child->setParent($this->getParentUser());

        $form = $this->createForm(ChildType::class, $child);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $child
                ->setParent($this->getParentUser())
                ->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($child);
            $entityManager->flush();

            $this->addFlash('success', 'Enfant ajoute avec succes.');

            return $this->redirectToRoute('app_front_child_index');
        }

        return $this->render('front_office/child/new.html.twig', [
            'form' => $form->createView(),
            'child' => $child,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Child $child): Response
    {
        $this->denyUnlessOwnChild($child);

        return $this->render('front_office/child/show.html.twig', [
            'child' => $child,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Child $child, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnChild($child);

        $form = $this->createForm(ChildType::class, $child);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $child
                ->setParent($this->getParentUser())
                ->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Enfant modifie avec succes.');

            return $this->redirectToRoute('app_front_child_index');
        }

        return $this->render('front_office/child/edit.html.twig', [
            'form' => $form->createView(),
            'child' => $child,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Child $child, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnChild($child);

        if (!$this->isCsrfTokenValid('delete_child_'.$child->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_front_child_index');
        }

        if ($child->getReservations()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer un enfant ayant deja des reservations.');

            return $this->redirectToRoute('app_front_child_index');
        }

        $entityManager->remove($child);
        $entityManager->flush();
        $this->addFlash('success', 'Enfant supprime avec succes.');

        return $this->redirectToRoute('app_front_child_index');
    }

    private function getParentUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user;
    }

    private function denyUnlessOwnChild(Child $child): void
    {
        if ($child->getParent() !== $this->getParentUser()) {
            throw $this->createAccessDeniedException('Acces refuse a cet enfant.');
        }
    }
}
