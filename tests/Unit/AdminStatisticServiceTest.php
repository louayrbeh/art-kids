<?php

namespace App\Tests\Unit;

use App\Service\AdminStatisticService;

class AdminStatisticServiceTest extends DatabaseKernelTestCase
{
    public function testDashboardCountsAndCollectionsComeFromFixtures(): void
    {
        self::bootKernel();

        /** @var AdminStatisticService $service */
        $service = static::getContainer()->get(AdminStatisticService::class);

        self::assertSame(5, $service->countUsers());
        self::assertSame(3, $service->countParents());
        self::assertSame(2, $service->countAdmins());
        self::assertSame(5, $service->countChildren());
        self::assertSame(5, $service->countCategories());
        self::assertSame(8, $service->countActivities());
        self::assertSame(6, $service->countReservations());
        self::assertSame(1, $service->countReservationsByStatus('EN_ATTENTE'));
        self::assertSame(4, $service->countReservationsByStatus('CONFIRMEE'));
        self::assertSame(1, $service->countReservationsByStatus('ANNULEE'));
        self::assertSame(5, $service->countActivitiesByStatus('OUVERTE'));
        self::assertSame(1, $service->countActivitiesByStatus('COMPLETE'));
        self::assertSame(1, $service->countActivitiesByStatus('ANNULEE'));
        self::assertSame(1, $service->countActivitiesByStatus('TERMINEE'));

        self::assertNotEmpty($service->getReservationsByStatus());
        self::assertNotEmpty($service->getActivitiesByStatus());
        self::assertNotEmpty($service->getReservationsByMonth());
        self::assertNotEmpty($service->getActivitiesByCategory());
        self::assertNotEmpty($service->getUsersByRole());
        self::assertNotEmpty($service->getTopReservedActivities());
        self::assertNotNull($service->getMostReservedActivity());
        self::assertNotNull($service->getMostPopularCategory());
        self::assertNotNull($service->getMostActiveParent());
        self::assertNotNull($service->getMostActiveChild());
        self::assertNotNull($service->getLatestReservation());
        self::assertNotNull($service->getLatestParent());
        self::assertGreaterThanOrEqual(0, $service->getAverageActivityFillRate());
        self::assertGreaterThanOrEqual(0, $service->getTotalAvailablePlaces());
        self::assertGreaterThan(0, $service->getTotalReservedPlaces());
        self::assertNotEmpty($service->getAlmostFullActivities());
        self::assertNotEmpty($service->getLatestReservations());
        self::assertNotEmpty($service->getUpcomingActivities());
    }
}
