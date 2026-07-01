<?php

namespace App\Controller\BackOffice;

use App\Service\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_back_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(StatisticService $statisticService): Response
    {
        return $this->render('back_office/admin/index.html.twig', [
            'summary' => $statisticService->getSummary(),
            'mostReserved' => $statisticService->getMostReservedActivities(),
        ]);
    }
}
