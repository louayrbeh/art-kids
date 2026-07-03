<?php

namespace App\Tests\Functional\FrontOffice;

use App\Entity\Activity;
use App\Entity\Reservation;
use App\Repository\ActivityRepository;
use App\Repository\ReservationRepository;
use App\Tests\Functional\FunctionalTestCase;

class ParentActivityReservationTest extends FunctionalTestCase
{
    public function testParentCanSeeAvailableActivitiesOnly(): void
    {
        $this->loginAs('parent@test.com');

        $this->client->request('GET', '/parent/activities');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Peinture creative 7 ans');
        self::assertSelectorTextContains('body', 'Initiation piano 10 ans');
        self::assertSelectorTextNotContains('body', 'Activite annulee');
        self::assertSelectorTextNotContains('body', 'Activite passee');
    }

    public function testParentCanReserveCompatibleActivityWithRedirect(): void
    {
        $this->loginAs('parent@test.com');

        /** @var ActivityRepository $activityRepository */
        $activityRepository = static::getContainer()->get(ActivityRepository::class);
        $activity = $activityRepository->findOneBy(['titre' => 'Peinture creative 7 ans']);
        self::assertInstanceOf(Activity::class, $activity);

        $this->client->request('GET', '/parent/activities/'.$activity->getId());
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Reserver', [
            'child_id' => $this->findChildIdByFirstName('Youssef'),
        ]);

        self::assertTrue(in_array($this->client->getResponse()->getStatusCode(), [302, 303], true));
        self::assertResponseRedirects('/parent/reservations');

        /** @var ReservationRepository $reservationRepository */
        $reservationRepository = static::getContainer()->get(ReservationRepository::class);
        self::assertTrue($reservationRepository->existsForChildAndActivity(
            static::getContainer()->get('doctrine')->getRepository(\App\Entity\Child::class)->find($this->findChildIdByFirstName('Youssef')),
            $activity
        ));
    }

    public function testUnavailableActivitiesCannotBeReservedFromUi(): void
    {
        $this->loginAs('parent@test.com');

        /** @var ActivityRepository $activityRepository */
        $activityRepository = static::getContainer()->get(ActivityRepository::class);
        $cancelled = $activityRepository->findOneBy(['titre' => 'Activite annulee']);
        $complete = $activityRepository->findOneBy(['titre' => 'Atelier complet']);
        $past = $activityRepository->findOneBy(['titre' => 'Activite passee']);

        self::assertInstanceOf(Activity::class, $cancelled);
        self::assertInstanceOf(Activity::class, $complete);
        self::assertInstanceOf(Activity::class, $past);

        $this->client->request('GET', '/parent/activities/'.$cancelled->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'n est plus reservable');

        $this->client->request('GET', '/parent/activities/'.$complete->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'n est plus reservable');

        $this->client->request('GET', '/parent/activities/'.$past->getId());
        self::assertResponseRedirects('/parent/activities');
    }

    public function testParentSeesOnlyOwnReservationsAndCanCancelOwnReservation(): void
    {
        $this->loginAs('parent@test.com');
        $this->client->request('GET', '/parent/reservations');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Sara Ben Ali');
        self::assertSelectorTextNotContains('body', 'Adam Trabelsi');

        /** @var ReservationRepository $reservationRepository */
        $reservationRepository = static::getContainer()->get(ReservationRepository::class);
        $reservation = $reservationRepository->findOneBy([], ['id' => 'ASC']);
        self::assertInstanceOf(Reservation::class, $reservation);

        $this->client->request('GET', '/parent/reservations');
        $form = $this->client->getCrawler()
            ->filter(sprintf('form[action$="/parent/reservations/%d/cancel"]', $reservation->getId()))
            ->form();
        $this->client->submit($form);

        self::assertTrue(in_array($this->client->getResponse()->getStatusCode(), [302, 303], true));
        $updatedReservation = $reservationRepository->find($reservation->getId());
        self::assertSame('ANNULEE', $updatedReservation?->getStatut()?->value);
    }

    public function testParentCannotCancelAnotherParentsReservation(): void
    {
        $this->loginAs('parent@test.com');

        /** @var ReservationRepository $reservationRepository */
        $reservationRepository = static::getContainer()->get(ReservationRepository::class);
        $otherReservation = null;
        foreach ($reservationRepository->findLatest(20) as $reservation) {
            if ('parent2@test.com' === $reservation->getChild()?->getParent()?->getEmail()) {
                $otherReservation = $reservation;
                break;
            }
        }

        self::assertInstanceOf(Reservation::class, $otherReservation);
        $this->client->request('GET', '/parent/reservations/'.$otherReservation->getId());
        self::assertResponseStatusCodeSame(403);
    }

    private function findChildIdByFirstName(string $prenom): int
    {
        $child = static::getContainer()->get('doctrine')->getRepository(\App\Entity\Child::class)->findOneBy(['prenom' => $prenom]);
        self::assertInstanceOf(\App\Entity\Child::class, $child);

        return $child->getId();
    }
}
