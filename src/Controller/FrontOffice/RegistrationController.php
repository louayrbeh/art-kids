<?php

namespace App\Controller\FrontOffice;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\FrontOffice\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_front_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        $user = new User();
        $user->setRoles([UserRole::ROLE_PARENT->value]);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte parent a ete cree. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_front_login');
        }

        return $this->render('front_office/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
