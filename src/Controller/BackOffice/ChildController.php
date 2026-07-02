<?php

namespace App\Controller\BackOffice;

use App\Entity\Child;
use App\Repository\ChildRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/children', name: 'app_back_child_')]
#[IsGranted('ROLE_ADMIN')]
class ChildController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ChildRepository $childRepository): Response
    {
        return $this->render('back_office/child/index.html.twig', [
            'children' => $childRepository->findAllWithParents(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Child $child): Response
    {
        return $this->render('back_office/child/show.html.twig', [
            'child' => $child,
        ]);
    }
}
