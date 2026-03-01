<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Module\Symfony\CodeceptTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Tests\App\Doctrine\TestDatabaseSetup;
use Tests\App\TestKernel;

class KernelTestCase extends CodeceptTestCase
{
    protected function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUpDatabase(EntityManagerInterface $em): void
    {
        TestDatabaseSetup::init($em);
    }
}
