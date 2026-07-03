<?php

namespace App\Tests\Functional\FrontOffice;

use App\Entity\Child;
use App\Repository\ChildRepository;
use App\Tests\Functional\FunctionalTestCase;

class ParentChildrenTest extends FunctionalTestCase
{
    public function testParentCanAccessDashboardAndOnlySeeOwnChildren(): void
    {
        $this->loginAs('parent@test.com');

        $this->client->request('GET', '/parent');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/parent/children');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Youssef');
        self::assertSelectorTextContains('body', 'Sara');
        self::assertSelectorTextNotContains('body', 'Adam');
    }

    public function testGuestIsRedirectedAndAdminCannotUseParentSpace(): void
    {
        $this->client->request('GET', '/parent');
        self::assertResponseRedirects('/login');

        $this->loginAs('admin@test.com');
        $this->client->request('GET', '/parent');
        self::assertResponseStatusCodeSame(403);
    }

    public function testParentCanCreateAndEditOwnChild(): void
    {
        $this->loginAs('parent@test.com');

        $this->client->request('GET', '/parent/children/new');
        $this->client->submitForm('Ajouter l enfant', [
            'child[nom]' => 'Nouveau',
            'child[prenom]' => 'Petit',
            'child[dateNaissance]' => '2020-07-03',
            'child[sexe]' => '0',
        ]);

        self::assertResponseRedirects('/parent/children');

        /** @var ChildRepository $childRepository */
        $childRepository = static::getContainer()->get(ChildRepository::class);
        $child = $childRepository->findOneBy(['prenom' => 'Petit', 'nom' => 'Nouveau']);

        self::assertInstanceOf(Child::class, $child);
        self::assertSame('parent@test.com', $child->getParent()?->getEmail());

        $this->client->request('GET', '/parent/children/'.$child->getId().'/edit');
        $this->client->submitForm('Enregistrer les modifications', [
            'child[nom]' => 'Nouveau',
            'child[prenom]' => 'Petit Modifie',
            'child[dateNaissance]' => '2020-07-03',
            'child[sexe]' => '0',
        ]);

        self::assertResponseRedirects('/parent/children');
        $updatedChild = $childRepository->find($child->getId());
        self::assertSame('Petit Modifie', $updatedChild?->getPrenom());
    }

    public function testParentCannotEditAnotherParentsChildAndInvalidBirthdateShowsError(): void
    {
        $this->loginAs('parent@test.com');

        /** @var ChildRepository $childRepository */
        $childRepository = static::getContainer()->get(ChildRepository::class);
        $otherChild = $childRepository->findOneBy(['prenom' => 'Adam']);
        self::assertInstanceOf(Child::class, $otherChild);

        $this->client->request('GET', '/parent/children/'.$otherChild->getId().'/edit');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/parent/children/new');
        $this->client->submitForm('Ajouter l enfant', [
            'child[nom]' => 'Futur',
            'child[prenom]' => 'Kid',
            'child[dateNaissance]' => '2100-01-01',
            'child[sexe]' => '0',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'ne peut pas etre dans le futur');
    }
}
