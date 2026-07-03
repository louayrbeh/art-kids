<?php

namespace App\Tests\Functional\FrontOffice;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Functional\FunctionalTestCase;

class PublicAuthenticationTest extends FunctionalTestCase
{
    public function testPublicPagesAreAccessible(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'ArtKids');

        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Connexion');

        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Inscription parent');
    }

    public function testVisitorCanRegisterAndGetsRoleParentOnly(): void
    {
        $this->client->request('GET', '/register');

        $this->client->submitForm('Creer mon compte', [
            'registration_form[nom]' => 'Tester',
            'registration_form[prenom]' => 'Parent',
            'registration_form[email]' => 'new-parent@test.com',
            'registration_form[telephone]' => '0123456789',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);

        self::assertResponseRedirects('/login');

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'new-parent@test.com']);

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isParent());
        self::assertFalse($user->isAdmin());
        self::assertTrue($user->isActive());
    }

    public function testDuplicateEmailShowsValidationError(): void
    {
        $this->client->request('GET', '/register');

        $this->client->submitForm('Creer mon compte', [
            'registration_form[nom]' => 'Tester',
            'registration_form[prenom]' => 'Parent',
            'registration_form[email]' => 'parent@test.com',
            'registration_form[telephone]' => '0123456789',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'deja utilisee');
    }

    public function testParentAndAdminCanLoginAndLogout(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'parent@test.com',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects('/post-login');
        $this->client->followRedirect();
        self::assertResponseRedirects('/parent');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/logout');
        self::assertResponseRedirects('/');

        self::ensureKernelShutdown();
        $adminClient = static::createClient();
        $adminClient->request('GET', '/login');
        $adminClient->submitForm('Se connecter', [
            '_username' => 'admin@test.com',
            '_password' => 'password',
        ]);

        self::assertResponseRedirects('/post-login');
        $adminClient->followRedirect();
        self::assertResponseRedirects('/admin');
    }

    public function testWrongPasswordKeepsUserOnLogin(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'parent@test.com',
            '_password' => 'wrong-password',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }
}
