<?php

namespace App\Tests\Unit;

use App\Tests\Support\DatabaseResetTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseKernelTestCase extends KernelTestCase
{
    use DatabaseResetTrait;

    public static function setUpBeforeClass(): void
    {
        self::resetDatabase();
    }
}
