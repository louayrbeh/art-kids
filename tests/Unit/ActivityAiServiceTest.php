<?php

namespace App\Tests\Unit;

use App\Service\ActivityAiService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AllowMockObjectsWithoutExpectations]
class ActivityAiServiceTest extends TestCase
{
    public function testGeneratesFallbackDescriptionWithoutOpenAiKey(): void
    {
        $service = new ActivityAiService(
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            '',
            'gpt-4o-mini',
        );

        $description = $service->generateDescription('Peinture creative', 'Peinture', 6, 10);

        self::assertNotSame('', trim($description));
        self::assertStringContainsString('Peinture creative', $description);
    }

    public function testFallsBackLocallyWhenOpenAiFails(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \RuntimeException('API error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $service = new ActivityAiService($httpClient, $logger, 'fake_key', 'gpt-4o-mini');
        $description = $service->generateDescription('Initiation piano', 'Musique', 8, 11);

        self::assertNotSame('', trim($description));
        self::assertStringContainsString('Initiation piano', $description);
    }
}
