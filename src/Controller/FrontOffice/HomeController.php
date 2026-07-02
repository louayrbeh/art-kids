<?php

namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_front_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front_office/home/index.html.twig', [
            'universes' => [
                [
                    'icon' => 'bi bi-palette2',
                    'title' => 'Dessin',
                    'description' => 'Exprimer ses idees et imaginer sans limites',
                    'theme' => 'blue',
                ],
                [
                    'icon' => 'bi bi-brush',
                    'title' => 'Peinture',
                    'description' => 'Jouer avec les couleurs et les textures',
                    'theme' => 'pink',
                ],
                [
                    'icon' => 'bi bi-music-note-beamed',
                    'title' => 'Musique',
                    'description' => 'Decouvrir les sons et developper son oreille',
                    'theme' => 'green',
                ],
                [
                    'icon' => 'bi bi-masks',
                    'title' => 'Theatre',
                    'description' => 'Jouer, se raconter et prendre confiance',
                    'theme' => 'gold',
                ],
                [
                    'icon' => 'bi bi-stars',
                    'title' => 'Danse',
                    'description' => 'Bouger, s\'exprimer et se depasser',
                    'theme' => 'violet',
                ],
                [
                    'icon' => 'bi bi-scissors',
                    'title' => 'Sculpture',
                    'description' => 'Creer avec ses mains et faconner le monde',
                    'theme' => 'peach',
                ],
            ],
            'stats' => [
                [
                    'icon' => 'bi-emoji-smile',
                    'value' => '500+',
                    'title' => 'Enfants inscrits',
                    'subtitle' => 'deja rejoints',
                    'theme' => 'blue',
                ],
                [
                    'icon' => 'bi-calendar-event',
                    'value' => '200+',
                    'title' => 'Activites disponibles',
                    'subtitle' => 'chaque mois',
                    'theme' => 'green',
                ],
                [
                    'icon' => 'bi-people',
                    'value' => '100+',
                    'title' => 'Parents satisfaits',
                    'subtitle' => 'nous font confiance',
                    'theme' => 'violet',
                ],
                [
                    'icon' => 'bi-shield-check',
                    'value' => '100%',
                    'title' => 'Securise',
                    'subtitle' => 'Donnees protegees',
                    'theme' => 'orange',
                ],
            ],
            'steps' => [
                [
                    'number' => '01',
                    'title' => 'Creez votre compte parent',
                    'description' => 'Inscrivez-vous en quelques instants pour acceder a votre espace dedie.',
                ],
                [
                    'number' => '02',
                    'title' => 'Ajoutez vos enfants',
                    'description' => 'Centralisez leurs informations et choisissez les activites adaptees a leur age.',
                ],
                [
                    'number' => '03',
                    'title' => 'Reservez et suivez facilement',
                    'description' => 'Consultez les disponibilites, l\'historique et les reservations en toute simplicite.',
                ],
            ],
        ]);
    }
}
