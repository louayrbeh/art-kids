<?php

namespace App\Controller\BackOffice;

use App\Service\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_back_admin_index', methods: ['GET'])]
    #[Route('', name: 'app_back_office_admin_dashboard', methods: ['GET'])]
    public function index(StatisticService $statisticService): Response
    {
        return $this->render('back_office/admin/dashboard.html.twig', [
            'dashboard' => $statisticService->getDashboardData(),
            'today' => new \DateTimeImmutable(),
        ]);
    }
}
