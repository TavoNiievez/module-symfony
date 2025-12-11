<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

abstract class CodeceptTestCase extends TestCase
{
    use BrowserAssertionsTrait;
    use CacheTrait;
    use ConsoleAssertionsTrait;
    use DoctrineAssertionsTrait;
    use DomCrawlerAssertionsTrait;
    use EventsAssertionsTrait;
    use FormAssertionsTrait;
    use HttpClientAssertionsTrait;
    use HttpKernelAssertionsTrait;
    use LoggerAssertionsTrait;
    use MailerAssertionsTrait;
    use MimeAssertionsTrait;
    use NotifierAssertionsTrait;
    use ParameterAssertionsTrait;
    use RouterAssertionsTrait;
    use SecurityAssertionsTrait;
    use ServicesAssertionsTrait;
    use SessionAssertionsTrait;
    use TimeAssertionsTrait;
    use TranslationAssertionsTrait;
    use TwigAssertionsTrait;
    use ValidatorAssertionsTrait;

    protected KernelBrowser $client;
    protected KernelInterface $kernel;
    protected bool $profilerEnabled = true;

    /** @var array<string, bool> */
    protected array $config = ['guard' => false, 'authenticator' => false];

    protected function setUp(): void
    {
        $this->kernel = $this->createKernel();
        $this->kernel->boot();

        if ($this->_getContainer()->has('doctrine.orm.entity_manager')) {
            /** @var EntityManagerInterface $em */
            $em = $this->_getContainer()->get('doctrine.orm.entity_manager');
            $this->setUpDatabase($em);
        }

        $this->client = new KernelBrowser($this->kernel);

        if ($this->profilerEnabled) {
            $this->client->enableProfiler();
        }
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        parent::tearDown();
    }

    protected function createKernel(): KernelInterface
    {
        $kernelClass = $this->getKernelClass();

        if (!class_exists($kernelClass)) {
            throw new RuntimeException(sprintf('Kernel class "%s" not found.', $kernelClass));
        }

        /** @var KernelInterface $kernel */
        $kernel = new $kernelClass('test', true);

        return $kernel;
    }

    protected function getKernelClass(): string
    {
        if (isset($_SERVER['KERNEL_CLASS']) && is_string($_SERVER['KERNEL_CLASS'])) {
            return $_SERVER['KERNEL_CLASS'];
        }

        if (isset($_ENV['KERNEL_CLASS']) && is_string($_ENV['KERNEL_CLASS'])) {
            return $_ENV['KERNEL_CLASS'];
        }

        if (class_exists('App\Kernel')) {
            return 'App\Kernel';
        }

        throw new LogicException('Kernel class not found. Please define KERNEL_CLASS in your phpunit.xml or .env file.');
    }

    protected function setUpDatabase(EntityManagerInterface $em): void
    {
        // Override this method to perform database setup
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function _getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->_getContainer()->get('doctrine.orm.entity_manager');
        return $em;
    }

    protected function getProfile(): ?Profile
    {
        $profile = $this->client->getProfile();

        if ($profile instanceof Profile) {
            return $profile;
        }

        /** @var Response $response */
        $response = $this->client->getResponse();

        if ($profile = $this->getProfileFromCache($response)) {
            return $profile;
        }

        /** @var Profiler $profiler */
        $profiler = $this->_getContainer()->get('profiler');
        /** @var Request $request */
        $request = $this->client->getRequest();

        $profile = $profiler->collect($request, $response);

        if ($profile instanceof Profile) {
            $this->cacheProfile($response, $profile);
            return $profile;
        }

        return null;
    }
}
