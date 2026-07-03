<?php

namespace App\Tests\Unit;

use App\Entity\Activity;
use App\Entity\Category;
use App\Entity\Child;
use App\Entity\User;
use App\Enum\SexeEnum;
use App\Enum\UserRole;
use App\Service\AiRecommendationService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AllowMockObjectsWithoutExpectations]
class AiRecommendationServiceTest extends TestCase
{
    public function testReturnsLocalExplanationWhenApiKeyIsMissing(): void
    {
        $service = new AiRecommendationService(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            '',
            'gpt-4o-mini',
        );

        $explanation = $service->explainRecommendation($this->createChild(), $this->createActivity());

        self::assertNotSame('', trim($explanation));
        self::assertStringContainsString('recommand', mb_strtolower($explanation));
    }

    public function testFallsBackLocallyWhenOpenAiFails(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \RuntimeException('OpenAI indisponible'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $service = new AiRecommendationService($httpClient, $logger, 'fake_key', 'gpt-4o-mini');
        $explanation = $service->explainRecommendation($this->createChild(), $this->createActivity());

        self::assertNotSame('', trim($explanation));
        self::assertStringContainsString('activite', mb_strtolower($explanation));
    }

    private function createChild(): Child
    {
        $parent = (new User())
            ->setNom('Parent')
            ->setPrenom('Test')
            ->setEmail('parent+'.uniqid().'@test.com')
            ->setRoles([UserRole::ROLE_PARENT->value])
            ->setPassword('hashed');

        return (new Child())
            ->setNom('Dupont')
            ->setPrenom('Ahmed')
            ->setDateNaissance(new \DateTimeImmutable('-7 years'))
            ->setSexe(SexeEnum::GARCON)
            ->setParent($parent);
    }

    private function createActivity(): Activity
    {
        $category = (new Category())->setNom('Peinture');

        return (new Activity())
            ->setTitre('Peinture creative')
            ->setDescription('Description')
            ->setCategory($category)
            ->setDateActivite(new \DateTimeImmutable('+7 days'))
            ->setHeureDebut(new \DateTimeImmutable('10:00'))
            ->setHeureFin(new \DateTimeImmutable('11:00'))
            ->setCapaciteMax(8)
            ->setAgeMin(6)
            ->setAgeMax(10);
    }
}
