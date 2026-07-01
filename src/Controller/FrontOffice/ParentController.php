<?php

namespace App\Controller\FrontOffice;

use App\Entity\User;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent', name: 'app_front_parent_')]
#[IsGranted('ROLE_PARENT')]
class ParentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        /** @var User $parent */
        $parent = $this->getUser();

        return $this->render('front_office/parent/index.html.twig', [
            'parent' => $parent,
            'reservationCount' => count($reservationRepository->findByParent($parent)),
        ]);
    }
}
