<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\App\Doctrine\TestDatabaseSetup;
use Tests\App\TestKernel;

abstract class CodeceptTestCase extends TestCase
{
    use BrowserAssertionsTrait;
    use ConsoleAssertionsTrait;
    use DoctrineAssertionsTrait;
    use DomCrawlerAssertionsTrait;
    use EventsAssertionsTrait;
    use FormAssertionsTrait;
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

    /** @var AbstractBrowser<Request, Response> */
    protected AbstractBrowser $client;
    protected TestKernel $kernel;
    protected bool $profilerEnabled = true;

    /** @var array<string, bool> */
    protected array $config = ['guard' => false, 'authenticator' => false];

    /** @var array<string, object> */
    protected array $persistentServices = [];

    /** @var array<string, object> */
    protected array $permanentServices = [];

    protected function setUp(): void
    {
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();

        /** @var EntityManagerInterface $em */
        $em = $this->_getContainer()->get('doctrine.orm.entity_manager');
        TestDatabaseSetup::init($em);

        $this->client = new KernelBrowser($this->kernel);

        if ($this->profilerEnabled) {
            /** @var KernelBrowser $client */
            $client = $this->client;
            $client->enableProfiler();
        }
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        parent::tearDown();
    }

    protected function getClient(): KernelBrowser
    {
        /** @var KernelBrowser */
        return $this->client;
    }

    protected function _getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }
        /** @var ContainerInterface */
        return $container;
    }

    protected function _getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->_getContainer()->get('doctrine.orm.entity_manager');
        return $em;
    }

    protected function getProfile(): ?Profile
    {
        /** @var KernelBrowser $client */
        $client = $this->client;
        $profile = $client->getProfile();

        if (!$profile) {
            /** @var Profiler $profiler */
            $profiler = $this->_getContainer()->get('profiler');
            /** @var Request $request */
            $request = $client->getRequest();
            /** @var Response $response */
            $response = $client->getResponse();
            $profile = $profiler->collect($request, $response);
        }

        return $profile instanceof Profile ? $profile : null;
    }
}
