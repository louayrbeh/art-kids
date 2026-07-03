<?php

namespace App\Controller\BackOffice;

use App\Service\AdminStatisticService;
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
    public function index(AdminStatisticService $adminStatisticService): Response
    {
        return $this->render('back_office/admin/dashboard.html.twig', [
            'dashboard' => $adminStatisticService->getDashboardData(),
            'today' => new \DateTimeImmutable(),
        ]);
    }
}
