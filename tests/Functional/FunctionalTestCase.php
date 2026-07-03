<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Support\DatabaseResetTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    use DatabaseResetTrait;

    protected KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        self::resetDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function loginAs(string $email): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user, sprintf('Utilisateur de test introuvable: %s', $email));
        $this->client->loginUser($user);

        return $user;
    }
}
