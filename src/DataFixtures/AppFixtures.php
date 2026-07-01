<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Category;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
use App\Enum\SexeEnum;
use App\Enum\UserRole;
use App\Service\ActivityAiService;
use App\Service\ExternalImageService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ActivityAiService $activityAiService,
        private readonly ExternalImageService $externalImageService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setNom('Admin')
            ->setPrenom('Super')
            ->setEmail('admin@artkids.test')
            ->setTelephone('0102030405')
            ->setRoles([UserRole::ROLE_ADMIN->value]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));

        $parent = (new User())
            ->setNom('Dupont')
            ->setPrenom('Marie')
            ->setEmail('parent@artkids.test')
            ->setTelephone('0607080910')
            ->setRoles([UserRole::ROLE_PARENT->value]);
        $parent->setPassword($this->passwordHasher->hashPassword($parent, 'parent123'));

        $manager->persist($admin);
        $manager->persist($parent);

        $childOne = (new Child())
            ->setNom('Dupont')
            ->setPrenom('Leo')
            ->setDateNaissance(new \DateTimeImmutable('-8 years'))
            ->setSexe(SexeEnum::GARCON);

        $childTwo = (new Child())
            ->setNom('Dupont')
            ->setPrenom('Emma')
            ->setDateNaissance(new \DateTimeImmutable('-10 years'))
            ->setSexe(SexeEnum::FILLE);

        $parent->addChild($childOne);
        $parent->addChild($childTwo);

        $manager->persist($childOne);
        $manager->persist($childTwo);

        $categoryNames = ['Dessin', 'Peinture', 'Musique', 'Theatre', 'Danse'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $category = (new Category())
                ->setNom($name)
                ->setDescription(sprintf('Categorie %s pour les activites artistiques des enfants.', $name))
                ->setImage($this->externalImageService->getPlaceholder($name));

            $categories[$name] = $category;
            $manager->persist($category);
        }

        $activityDefinitions = [
            ['Atelier crayons magiques', 'Dessin', '+3 days', '10:00', '11:30', 12, 5, 8, '15.00', 'Salle A'],
            ['Peinture au doigt', 'Peinture', '+4 days', '14:00', '15:30', 10, 4, 7, '12.50', 'Salle B'],
            ['Initiation piano', 'Musique', '+5 days', '09:30', '11:00', 8, 7, 11, '18.00', 'Studio musique'],
            ['Jeux de scene', 'Theatre', '+6 days', '13:30', '15:00', 14, 8, 12, '16.00', 'Scene 1'],
            ['Danse creative', 'Danse', '+7 days', '10:30', '12:00', 16, 6, 10, '14.00', 'Salle de danse'],
            ['Croquis nature', 'Dessin', '+8 days', '15:00', '16:30', 10, 8, 12, '13.50', 'Jardin'],
            ['Aquarelle debutant', 'Peinture', '+9 days', '10:00', '11:30', 9, 7, 12, '17.00', 'Salle couleur'],
            ['Percussions junior', 'Musique', '+10 days', '14:30', '16:00', 10, 8, 12, '19.00', 'Studio rythme'],
            ['Impro theatrale', 'Theatre', '+11 days', '09:00', '10:30', 8, 9, 12, '20.00', 'Scene 2'],
            ['Choregraphie kids', 'Danse', '+12 days', '16:00', '17:30', 12, 8, 11, '18.50', 'Salle de danse'],
        ];

        $activities = [];
        foreach ($activityDefinitions as [$title, $categoryName, $date, $start, $end, $capacity, $ageMin, $ageMax, $price, $place]) {
            $activity = (new Activity())
                ->setTitre($title)
                ->setDescription($this->activityAiService->generateDescription($title, $categoryName))
                ->setCategory($categories[$categoryName])
                ->setDateActivite(new \DateTimeImmutable($date))
                ->setHeureDebut(new \DateTimeImmutable($start))
                ->setHeureFin(new \DateTimeImmutable($end))
                ->setCapaciteMax($capacity)
                ->setAgeMin($ageMin)
                ->setAgeMax($ageMax)
                ->setPrix($price)
                ->setLieu($place)
                ->setImage($this->externalImageService->getPlaceholder($title))
                ->setStatut(ActivityStatusEnum::OUVERTE);

            $activities[] = $activity;
            $manager->persist($activity);
        }

        $reservationOne = (new Reservation())
            ->setStatut(ReservationStatusEnum::CONFIRMEE);
        $childOne->addReservation($reservationOne);
        $activities[0]->addReservation($reservationOne);

        $reservationTwo = (new Reservation())
            ->setStatut(ReservationStatusEnum::EN_ATTENTE);
        $childTwo->addReservation($reservationTwo);
        $activities[2]->addReservation($reservationTwo);

        $reservationThree = (new Reservation())
            ->setStatut(ReservationStatusEnum::ANNULEE);
        $childOne->addReservation($reservationThree);
        $activities[4]->addReservation($reservationThree);

        $manager->persist($reservationOne);
        $manager->persist($reservationTwo);
        $manager->persist($reservationThree);

        foreach ($activities as $activity) {
            $activity->updateStatutIfNeeded();
        }

        $manager->flush();
    }
}
