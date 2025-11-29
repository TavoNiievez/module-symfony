<?php

namespace Tests;

use Codeception\Module\Symfony\ValidatorAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tests\_app\Entity\ValidEntity;

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
        return \Tests\_app\TestKernel::class;
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

    public function testDontSeeViolatedConstraint(): void
    {
        $user = ValidEntity::create('test@example.com', 'password123');

        $this->dontSeeViolatedConstraint($user);
        $this->dontSeeViolatedConstraint($user, 'email');
        $this->dontSeeViolatedConstraint($user, 'email', Assert\Email::class);

        $user->setEmail('invalid_email');
        $this->dontSeeViolatedConstraint($user, 'password');

        $user->setEmail('test@example.com');
        $user->setPassword('weak');
        $this->dontSeeViolatedConstraint($user, 'email');
        $this->dontSeeViolatedConstraint($user, 'password', Assert\NotBlank::class);
    }

    public function testSeeViolatedConstraint(): void
    {
        $user = ValidEntity::create('invalid_email', 'password123');

        $this->seeViolatedConstraint($user);
        $this->seeViolatedConstraint($user, 'email');

        $user->setEmail('test@example.com');
        $user->setPassword('weak');
        $this->seeViolatedConstraint($user);
        $this->seeViolatedConstraint($user, 'password');
        $this->seeViolatedConstraint($user, 'password', Assert\Length::class);
    }

    public function testSeeViolatedConstraintCount(): void
    {
        $user = ValidEntity::create('invalid_email', 'weak');

        $this->seeViolatedConstraintsCount(2, $user);
        $this->seeViolatedConstraintsCount(1, $user, 'email');

        $user->setEmail('test@example.com');

        $this->seeViolatedConstraintsCount(1, $user);
        $this->seeViolatedConstraintsCount(0, $user, 'email');
    }

    public function testSeeViolatedConstraintMessageContains(): void
    {
        $user = ValidEntity::create('invalid_email', 'weak');

        $this->seeViolatedConstraintMessage('valid email', $user, 'email');

        $user->setEmail('');
        $this->seeViolatedConstraintMessage('should not be blank', $user, 'email');
        $this->seeViolatedConstraintMessage('This value is too short', $user, 'email');
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
