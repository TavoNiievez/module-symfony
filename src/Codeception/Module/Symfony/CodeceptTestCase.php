<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\DataCollector\TwigDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollector;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Notifier\DataCollector\NotificationDataCollector;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;
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

    /**
     * @phpstan-return (
     *     $name is DataCollectorName::EVENTS ? EventDataCollector :
     *     ($name is DataCollectorName::FORM ? FormDataCollector :
     *     ($name is DataCollectorName::HTTP_CLIENT ? HttpClientDataCollector :
     *     ($name is DataCollectorName::LOGGER ? LoggerDataCollector :
     *     ($name is DataCollectorName::TIME ? TimeDataCollector :
     *     ($name is DataCollectorName::TRANSLATION ? TranslationDataCollector :
     *     ($name is DataCollectorName::TWIG ? TwigDataCollector :
     *     ($name is DataCollectorName::SECURITY ? SecurityDataCollector :
     *     ($name is DataCollectorName::MAILER ? MessageDataCollector :
     *     ($name is DataCollectorName::NOTIFIER ? NotificationDataCollector :
     *      DataCollectorInterface
     *     )))))))))
     * )
     */
    protected function grabCollector(DataCollectorName $name, string $function = '', ?string $message = null): DataCollectorInterface
    {
        /** @var KernelBrowser $client */
        $client = $this->client;
        $profile = $client->getProfile();
        if (!$profile) {
            /** @var Profiler $profiler */
            $profiler = $this->_getContainer()->get('profiler');
            /** @var Request $request */
            $request = $this->client->getRequest();
            /** @var Response $response */
            $response = $this->client->getResponse();
            $profile = $profiler->collect($request, $response);
        }

        if (!$profile) {
             throw new \RuntimeException('Profiler not enabled or failed to collect profile');
        }

        return $profile->getCollector($name->value);
    }
}
