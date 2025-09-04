<?php

namespace Tests;

use Codeception\Module\Symfony\ValidatorAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ValidatorAssertionsTest extends KernelTestCase
{
    use ValidatorAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
    }

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    public function testValidatorAssertions(): void
    {
        $invalid = new \ValidEntity();
        $valid = new \ValidEntity('John', 'abcd');

        $this->seeViolatedConstraint($invalid);
        $this->seeViolatedConstraint($invalid, 'name');
        $this->seeViolatedConstraint($invalid, 'short', Assert\Length::class);
        $this->seeViolatedConstraintsCount(2, $invalid);
        $this->seeViolatedConstraintsCount(1, $invalid, 'name');
        $this->seeViolatedConstraintMessage('too short', $invalid, 'short');
        $this->dontSeeViolatedConstraint($valid);
        $this->dontSeeViolatedConstraint($invalid, 'short', Assert\NotBlank::class);
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
