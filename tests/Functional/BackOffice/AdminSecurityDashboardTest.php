<?php

namespace App\Tests\Functional\BackOffice;

use App\Tests\Functional\FunctionalTestCase;

class AdminSecurityDashboardTest extends FunctionalTestCase
{
    public function testVisitorMustLoginForAdminDashboard(): void
    {
        $this->client->request('GET', '/admin');
        self::assertResponseRedirects('/login');
    }

    public function testParentCannotAccessAdminDashboard(): void
    {
        $this->loginAs('parent@test.com');
        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminDashboardLoadsWithDynamicStatisticsAndCharts(): void
    {
        $this->loginAs('admin@test.com');
        $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Tableau de bord statistique ArtKids');
        self::assertSelectorExists('#reservationsStatusChart');
        self::assertSelectorExists('#activitiesCategoryChart');
        self::assertSelectorTextContains('body', 'Utilisateurs');
        self::assertSelectorTextContains('body', 'Dernieres reservations');
        self::assertSelectorTextContains('body', 'Prochaines activites');
    }
}
