<?php

namespace App\Controller\FrontOffice;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    #[Route('/login', name: 'app_front_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        return $this->render('front_office/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    #[Route('/logout', name: 'app_front_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the firewall logout.');
    }

    #[Route('/post-login', name: 'app_post_login_redirect', methods: ['GET'])]
    public function postLoginRedirect(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_front_login');
        }

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_back_admin_index');
        }

        if ($user->isParent()) {
            return $this->redirectToRoute('app_front_parent_index');
        }

        return $this->redirectToRoute('app_front_home');
    }
}
