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
        $users = [
            'admin_dev' => $this->createUser('Admin', 'Super', 'admin@artkids.test', '0102030405', 'admin123', UserRole::ROLE_ADMIN),
            'parent_dev' => $this->createUser('Dupont', 'Marie', 'parent@artkids.test', '0607080910', 'parent123', UserRole::ROLE_PARENT),
            'admin_test' => $this->createUser('Admin', 'Test', 'admin@test.com', '0111222333', 'password', UserRole::ROLE_ADMIN),
            'parent_test' => $this->createUser('Ben Ali', 'Ahmed', 'parent@test.com', '0555001100', 'password', UserRole::ROLE_PARENT),
            'parent_test_two' => $this->createUser('Trabelsi', 'Nour', 'parent2@test.com', '0555002200', 'password', UserRole::ROLE_PARENT),
        ];

        foreach ($users as $user) {
            $manager->persist($user);
        }

        $children = [
            'leo' => $this->createChild($users['parent_dev'], 'Dupont', 'Leo', '-8 years', SexeEnum::GARCON),
            'emma' => $this->createChild($users['parent_dev'], 'Dupont', 'Emma', '-10 years', SexeEnum::FILLE),
            'ahmed7' => $this->createChild($users['parent_test'], 'Ben Ali', 'Youssef', '-7 years', SexeEnum::GARCON),
            'ahmed10' => $this->createChild($users['parent_test'], 'Ben Ali', 'Sara', '-10 years', SexeEnum::FILLE),
            'nour8' => $this->createChild($users['parent_test_two'], 'Trabelsi', 'Adam', '-8 years', SexeEnum::GARCON),
        ];

        foreach ($children as $child) {
            $manager->persist($child);
        }

        $categories = [];
        foreach (['Peinture', 'Musique', 'Theatre', 'Dessin', 'Danse'] as $name) {
            $category = (new Category())
                ->setNom($name)
                ->setDescription(sprintf('Categorie %s pour les activites artistiques des enfants.', $name))
                ->setImage($this->externalImageService->getPlaceholder($name));

            $categories[$name] = $category;
            $manager->persist($category);
        }

        $activities = [
            'peinture_7' => $this->createActivity('Peinture creative 7 ans', $categories['Peinture'], '+5 days', '10:00', '11:30', 6, 6, 8, '15.00', 'Salle Couleurs'),
            'theatre_10' => $this->createActivity('Theatre expression 10 ans', $categories['Theatre'], '+6 days', '14:00', '15:30', 8, 9, 11, '18.00', 'Scene Junior'),
            'complete' => $this->createActivity('Atelier complet', $categories['Dessin'], '+7 days', '09:30', '11:00', 1, 7, 9, '12.00', 'Salle A'),
            'cancelled' => $this->createActivity('Activite annulee', $categories['Musique'], '+8 days', '13:00', '14:30', 10, 7, 10, '16.00', 'Studio 2', ActivityStatusEnum::ANNULEE),
            'past' => $this->createActivity('Activite passee', $categories['Danse'], '-2 days', '15:00', '16:30', 12, 6, 10, '14.00', 'Salle Danse'),
            'future_open' => $this->createActivity('Musique future ouverte', $categories['Musique'], '+9 days', '11:00', '12:30', 10, 8, 11, '17.00', 'Studio Rythme'),
            'limited' => $this->createActivity('Activite capacite limitee', $categories['Peinture'], '+10 days', '16:00', '17:00', 3, 7, 10, '19.00', 'Atelier 3'),
            'music_10' => $this->createActivity('Initiation piano 10 ans', $categories['Musique'], '+11 days', '09:00', '10:30', 5, 9, 11, '20.00', 'Studio Piano'),
        ];

        foreach ($activities as $activity) {
            $manager->persist($activity);
        }

        $reservations = [
            $this->createReservation($children['ahmed7'], $activities['complete'], ReservationStatusEnum::CONFIRMEE),
            $this->createReservation($children['ahmed10'], $activities['music_10'], ReservationStatusEnum::EN_ATTENTE),
            $this->createReservation($children['ahmed7'], $activities['cancelled'], ReservationStatusEnum::ANNULEE),
            $this->createReservation($children['nour8'], $activities['limited'], ReservationStatusEnum::CONFIRMEE),
            $this->createReservation($children['leo'], $activities['limited'], ReservationStatusEnum::CONFIRMEE),
            $this->createReservation($children['emma'], $activities['future_open'], ReservationStatusEnum::CONFIRMEE),
        ];

        foreach ($reservations as $reservation) {
            $manager->persist($reservation);
        }

        foreach ($activities as $activity) {
            $activity->updateStatutIfNeeded();
        }

        $manager->flush();
    }

    private function createUser(
        string $nom,
        string $prenom,
        string $email,
        ?string $telephone,
        string $password,
        UserRole $role,
    ): User {
        $user = (new User())
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setEmail($email)
            ->setTelephone($telephone)
            ->setRoles([$role->value])
            ->setIsActive(true);

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        return $user;
    }

    private function createChild(
        User $parent,
        string $nom,
        string $prenom,
        string $dateNaissance,
        SexeEnum $sexe,
    ): Child {
        $child = (new Child())
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setDateNaissance(new \DateTimeImmutable($dateNaissance))
            ->setSexe($sexe);

        $parent->addChild($child);

        return $child;
    }

    private function createActivity(
        string $title,
        Category $category,
        string $date,
        string $start,
        string $end,
        int $capacity,
        int $ageMin,
        int $ageMax,
        ?string $price,
        ?string $place,
        ActivityStatusEnum $status = ActivityStatusEnum::OUVERTE,
    ): Activity {
        return (new Activity())
            ->setTitre($title)
            ->setDescription($this->activityAiService->generateDescription($title, $category->getNom(), $ageMin, $ageMax))
            ->setCategory($category)
            ->setDateActivite(new \DateTimeImmutable($date))
            ->setHeureDebut(new \DateTimeImmutable($start))
            ->setHeureFin(new \DateTimeImmutable($end))
            ->setCapaciteMax($capacity)
            ->setAgeMin($ageMin)
            ->setAgeMax($ageMax)
            ->setPrix($price)
            ->setLieu($place)
            ->setImage($this->externalImageService->getPlaceholder($title))
            ->setStatut($status);
    }

    private function createReservation(Child $child, Activity $activity, ReservationStatusEnum $status): Reservation
    {
        $reservation = (new Reservation())
            ->setStatut($status);

        $child->addReservation($reservation);
        $activity->addReservation($reservation);

        return $reservation;
    }
}
