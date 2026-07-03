<?php

namespace App\Tests\Support;

use App\DataFixtures\AppFixtures;
use App\Service\ActivityAiService;
use App\Service\ExternalImageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait DatabaseResetTrait
{
    protected static function resetDatabase(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);

        if ([] !== $metadata) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        $fixtures = new AppFixtures(
            $container->get(UserPasswordHasherInterface::class),
            $container->get(ActivityAiService::class),
            $container->get(ExternalImageService::class),
        );
        $fixtures->load($entityManager);
        $entityManager->clear();

        self::ensureKernelShutdown();
    }
}
