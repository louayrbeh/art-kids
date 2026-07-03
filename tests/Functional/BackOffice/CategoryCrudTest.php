<?php

namespace App\Tests\Functional\BackOffice;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Tests\Functional\FunctionalTestCase;

class CategoryCrudTest extends FunctionalTestCase
{
    public function testAdminCanCreateAndEditCategory(): void
    {
        $this->loginAs('admin@test.com');

        $this->client->request('GET', '/admin/categories/new');
        $this->client->submitForm('Enregistrer', [
            'category[nom]' => 'Sculpture Test',
            'category[description]' => 'Categorie creee pendant les tests',
        ]);

        self::assertResponseRedirects('/admin/categories');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category = $categoryRepository->findOneBy(['nom' => 'Sculpture Test']);
        self::assertInstanceOf(Category::class, $category);

        $this->client->request('GET', '/admin/categories/'.$category->getId().'/edit');
        $this->client->submitForm('Enregistrer', [
            'category[nom]' => 'Sculpture Test Modifiee',
            'category[description]' => 'Description modifiee',
        ]);

        self::assertResponseRedirects('/admin/categories');
        self::assertNotNull($categoryRepository->findOneBy(['nom' => 'Sculpture Test Modifiee']));
    }

    public function testAdminCannotDeleteCategoryWithActivities(): void
    {
        $this->loginAs('admin@test.com');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category = $categoryRepository->findOneBy(['nom' => 'Peinture']);
        self::assertInstanceOf(Category::class, $category);

        $this->client->request('GET', '/admin/categories');
        $form = $this->client->getCrawler()
            ->filter(sprintf('form[action$="/admin/categories/%d/delete"]', $category->getId()))
            ->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/categories');
        self::assertNotNull($categoryRepository->find($category->getId()));
    }

    public function testAdminCanDeleteCategoryWithoutActivities(): void
    {
        $this->loginAs('admin@test.com');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category = new Category();
        $category->setNom('Temp Delete');
        $category->setDescription('A supprimer');

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($category);
        $entityManager->flush();

        $this->client->request('GET', '/admin/categories');
        $form = $this->client->getCrawler()
            ->filter(sprintf('form[action$="/admin/categories/%d/delete"]', $category->getId()))
            ->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/categories');
        self::assertNull($categoryRepository->find($category->getId()));
    }
}
