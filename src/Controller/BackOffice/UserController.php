<?php

namespace App\Controller\BackOffice;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\BackOffice\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'app_back_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('back_office/user/index.html.twig', [
            'users' => $userRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $user
            ->setRoles([UserRole::ROLE_PARENT->value])
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles([$this->getSelectedRole($form)]);
            $user->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur cree avec succes.');

            return $this->redirectToRoute('app_back_user_index');
        }

        return $this->render('back_office/user/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('back_office/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRole = $this->getSelectedRole($form);
            $isActive = (bool) $form->get('isActive')->getData();

            if ($this->isCurrentUser($user) && UserRole::ROLE_ADMIN->value !== $selectedRole) {
                $this->addFlash('error', 'Vous ne pouvez pas retirer votre propre role administrateur.');

                return $this->redirectToRoute('app_back_user_edit', ['id' => $user->getId()]);
            }

            if ($this->isCurrentUser($user) && !$isActive) {
                $this->addFlash('error', 'Vous ne pouvez pas desactiver votre propre compte.');

                return $this->redirectToRoute('app_back_user_edit', ['id' => $user->getId()]);
            }

            if ($this->wouldRemoveLastAdminRole($user, $selectedRole, $userRepository)) {
                $this->addFlash('error', 'Impossible de retirer le role du dernier administrateur.');

                return $this->redirectToRoute('app_back_user_edit', ['id' => $user->getId()]);
            }

            if ($this->wouldDeactivateLastActiveAdmin($user, $selectedRole, $isActive, $userRepository)) {
                $this->addFlash('error', 'Impossible de desactiver le dernier administrateur actif.');

                return $this->redirectToRoute('app_back_user_edit', ['id' => $user->getId()]);
            }

            $plainPassword = (string) $form->get('plainPassword')->getData();
            if ('' !== $plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $user
                ->setRoles([$selectedRole])
                ->setIsActive($isActive);

            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifie avec succes.');

            return $this->redirectToRoute('app_back_user_index');
        }

        return $this->render('back_office/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_back_user_index');
        }

        $newStatus = !$user->isActive();

        if ($this->isCurrentUser($user) && !$newStatus) {
            $this->addFlash('error', 'Vous ne pouvez pas desactiver votre propre compte.');

            return $this->redirectToRoute('app_back_user_index');
        }

        if ($this->wouldDeactivateLastActiveAdmin($user, null, $newStatus, $userRepository)) {
            $this->addFlash('error', 'Impossible de desactiver le dernier administrateur actif.');

            return $this->redirectToRoute('app_back_user_index');
        }

        $user->setIsActive($newStatus);
        $entityManager->flush();

        $this->addFlash('success', $newStatus ? 'Utilisateur active avec succes.' : 'Utilisateur desactive avec succes.');

        return $this->redirectToRoute('app_back_user_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_back_user_index');
        }

        if ($this->isCurrentUser($user)) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('app_back_user_index');
        }

        if ($user->isAdmin() && $userRepository->countAdmins() <= 1) {
            $this->addFlash('error', 'Impossible de supprimer le dernier administrateur.');

            return $this->redirectToRoute('app_back_user_index');
        }

        if ($user->getChildren()->count() > 0) {
            if ($this->wouldDeactivateLastActiveAdmin($user, null, false, $userRepository)) {
                $this->addFlash('error', 'Impossible de desactiver le dernier administrateur actif.');

                return $this->redirectToRoute('app_back_user_index');
            }

            $user->setIsActive(false);
            $entityManager->flush();
            $this->addFlash('warning', 'Utilisateur desactive a la place d une suppression car il possede un historique.');

            return $this->redirectToRoute('app_back_user_index');
        }

        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur supprime avec succes.');

        return $this->redirectToRoute('app_back_user_index');
    }

    private function getSelectedRole(FormInterface $form): string
    {
        $selectedRole = (string) $form->get('roles')->getData();

        return in_array($selectedRole, [UserRole::ROLE_PARENT->value, UserRole::ROLE_ADMIN->value], true)
            ? $selectedRole
            : UserRole::ROLE_PARENT->value;
    }

    private function isCurrentUser(User $user): bool
    {
        $currentUser = $this->getUser();

        return $currentUser instanceof User && $currentUser->getId() === $user->getId();
    }

    private function wouldRemoveLastAdminRole(User $user, ?string $selectedRole, UserRepository $userRepository): bool
    {
        return $user->isAdmin()
            && UserRole::ROLE_ADMIN->value !== $selectedRole
            && $userRepository->countAdmins() <= 1;
    }

    private function wouldDeactivateLastActiveAdmin(
        User $user,
        ?string $selectedRole,
        bool $isActive,
        UserRepository $userRepository,
    ): bool {
        $keepsAdminRole = null === $selectedRole || UserRole::ROLE_ADMIN->value === $selectedRole;

        return $user->isAdmin()
            && $user->isActive()
            && (!$keepsAdminRole || !$isActive)
            && $userRepository->countActiveAdmins() <= 1;
    }
}
