<?php

namespace App\Tests\Functional\BackOffice;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
use App\Tests\Functional\FunctionalTestCase;

class ActivityCrudTest extends FunctionalTestCase
{
    public function testAdminCanCreateEditAndShowActivity(): void
    {
        $this->loginAs('admin@test.com');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category = $categoryRepository->findOneBy(['nom' => 'Peinture']);
        self::assertNotNull($category);

        $imagePath = $this->createTemporaryPng();

        $this->client->request('GET', '/admin/activities/new');
        $this->client->submitForm('Enregistrer', [
            'activity[titre]' => 'Activite test upload',
            'activity[description]' => 'Description de test',
            'activity[dateActivite]' => (new \DateTimeImmutable('+15 days'))->format('Y-m-d'),
            'activity[heureDebut]' => '10:00',
            'activity[heureFin]' => '11:30',
            'activity[capaciteMax]' => '6',
            'activity[ageMin]' => '6',
            'activity[ageMax]' => '10',
            'activity[prix]' => '15',
            'activity[statut]' => '0',
            'activity[lieu]' => 'Salle de test',
            'activity[category]' => (string) $category->getId(),
            'activity[imageFile]' => $imagePath,
        ]);

        self::assertResponseRedirects('/admin/activities');

        /** @var ActivityRepository $activityRepository */
        $activityRepository = static::getContainer()->get(ActivityRepository::class);
        $activity = $activityRepository->findOneBy(['titre' => 'Activite test upload']);
        self::assertInstanceOf(Activity::class, $activity);
        self::assertNotNull($activity->getImage());

        $this->client->request('GET', '/admin/activities/'.$activity->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Activite test upload');

        $this->client->request('GET', '/admin/activities/'.$activity->getId().'/edit');
        $this->client->submitForm('Enregistrer', [
            'activity[titre]' => 'Activite test upload modifiee',
            'activity[description]' => 'Description modifiee',
            'activity[dateActivite]' => (new \DateTimeImmutable('+16 days'))->format('Y-m-d'),
            'activity[heureDebut]' => '10:30',
            'activity[heureFin]' => '12:00',
            'activity[capaciteMax]' => '6',
            'activity[ageMin]' => '6',
            'activity[ageMax]' => '10',
            'activity[prix]' => '18',
            'activity[statut]' => '0',
            'activity[lieu]' => 'Salle modifiee',
            'activity[category]' => (string) $category->getId(),
        ]);

        self::assertResponseRedirects('/admin/activities');
        self::assertNotNull($activityRepository->findOneBy(['titre' => 'Activite test upload modifiee']));
    }

    public function testInvalidActivityDataIsRejected(): void
    {
        $this->loginAs('admin@test.com');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $category = $categoryRepository->findOneBy(['nom' => 'Musique']);
        self::assertNotNull($category);

        $this->client->request('GET', '/admin/activities/new');
        $this->client->submitForm('Enregistrer', [
            'activity[titre]' => 'Activite invalide',
            'activity[description]' => 'Description invalide',
            'activity[dateActivite]' => (new \DateTimeImmutable('-1 day'))->format('Y-m-d'),
            'activity[heureDebut]' => '12:00',
            'activity[heureFin]' => '11:00',
            'activity[capaciteMax]' => '0',
            'activity[ageMin]' => '10',
            'activity[ageMax]' => '8',
            'activity[prix]' => '10',
            'activity[statut]' => '0',
            'activity[lieu]' => 'Salle',
            'activity[category]' => (string) $category->getId(),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'superieure a la date');
    }

    public function testActivityAiFallbackEndpointReturnsJson(): void
    {
        $this->loginAs('admin@test.com');

        $this->client->request('GET', '/admin/activities/new');
        $crawler = $this->client->getCrawler();
        $csrfToken = $crawler->filter('[data-activity-ai-csrf]')->attr('value');

        $this->client->xmlHttpRequest('POST', '/admin/activities/generate-description', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
        ], json_encode([
            'title' => 'Peinture creative',
            'category' => 'Peinture',
            'ageMin' => 6,
            'ageMax' => 10,
        ], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($data['success']);
        self::assertNotSame('', trim((string) $data['description']));
    }

    public function testAdminCannotDeleteActivityWithActiveReservations(): void
    {
        $this->loginAs('admin@test.com');

        /** @var ActivityRepository $activityRepository */
        $activityRepository = static::getContainer()->get(ActivityRepository::class);
        $activity = $activityRepository->findOneBy(['titre' => 'Atelier complet']);
        self::assertInstanceOf(Activity::class, $activity);

        $this->client->request('GET', '/admin/activities');
        $form = $this->client->getCrawler()
            ->filter(sprintf('form[action$="/admin/activities/%d/delete"]', $activity->getId()))
            ->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/activities');
        self::assertNotNull($activityRepository->find($activity->getId()));
    }

    private function createTemporaryPng(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'artkids').'.png';
        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn0K8QAAAAASUVORK5CYII=', true)
        );

        return $path;
    }
}
