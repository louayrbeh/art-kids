<?php

namespace App\Tests\Functional\BackOffice;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Functional\FunctionalTestCase;

class UserCrudTest extends FunctionalTestCase
{
    public function testAdminCanCreateParentAndAdminUsers(): void
    {
        $this->loginAs('admin@test.com');

        $this->client->request('GET', '/admin/users/new');
        $this->client->submitForm('Enregistrer', [
            'user[nom]' => 'Parent',
            'user[prenom]' => 'Created',
            'user[email]' => 'created-parent@test.com',
            'user[telephone]' => '0101010101',
            'user[roles]' => 'ROLE_PARENT',
            'user[plainPassword]' => 'password123',
            'user[isActive]' => '1',
        ]);

        self::assertResponseRedirects('/admin/users');

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $parent = $userRepository->findOneBy(['email' => 'created-parent@test.com']);
        self::assertInstanceOf(User::class, $parent);
        self::assertTrue($parent->isParent());

        $this->client->request('GET', '/admin/users/new');
        $this->client->submitForm('Enregistrer', [
            'user[nom]' => 'Admin',
            'user[prenom]' => 'Created',
            'user[email]' => 'created-admin@test.com',
            'user[telephone]' => '0202020202',
            'user[roles]' => 'ROLE_ADMIN',
            'user[plainPassword]' => 'password123',
            'user[isActive]' => '1',
        ]);

        self::assertResponseRedirects('/admin/users');
        $admin = $userRepository->findOneBy(['email' => 'created-admin@test.com']);
        self::assertInstanceOf(User::class, $admin);
        self::assertTrue($admin->isAdmin());
    }

    public function testAdminCannotCreateDuplicateEmailAndCannotDeleteSelf(): void
    {
        $this->loginAs('admin@test.com');

        $this->client->request('GET', '/admin/users/new');
        $this->client->submitForm('Enregistrer', [
            'user[nom]' => 'Admin',
            'user[prenom]' => 'Duplicate',
            'user[email]' => 'admin@test.com',
            'user[telephone]' => '0303030303',
            'user[roles]' => 'ROLE_ADMIN',
            'user[plainPassword]' => 'password123',
            'user[isActive]' => '1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'deja utilisee');

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@test.com']);
        self::assertInstanceOf(User::class, $admin);

        $this->client->request('GET', '/admin/users');
        $form = $this->client->getCrawler()
            ->filter(sprintf('form[action$="/admin/users/%d/delete"]', $admin->getId()))
            ->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/users');
        self::assertNotNull($userRepository->find($admin->getId()));
    }
}
