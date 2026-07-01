<?php

namespace App\Controller\FrontOffice;

use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_front_home', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository): Response
    {
        $activities = array_slice($activityRepository->findOpenFutureActivities(), 0, 3);

        return $this->render('front_office/home/index.html.twig', [
            'activities' => $activities,
        ]);
    }
}
