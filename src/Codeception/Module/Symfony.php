<?php

declare(strict_types=1);

namespace Codeception\Module;

use BadMethodCallException;
use Codeception\Exception\ModuleRequireException;
use Codeception\Lib\Connector\Symfony as SymfonyConnector;
use Codeception\Lib\Framework;
use Codeception\Lib\Interfaces\DoctrineProvider;
use Codeception\Lib\Interfaces\PartedModule;
use Codeception\Module\Symfony\BrowserAssertionsTrait;
use Codeception\Module\Symfony\ConsoleAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\DoctrineAssertionsTrait;
use Codeception\Module\Symfony\DomCrawlerAssertionsTrait;
use Codeception\Module\Symfony\EventsAssertionsTrait;
use Codeception\Module\Symfony\FormAssertionsTrait;
use Codeception\Module\Symfony\HttpClientAssertionsTrait;
use Codeception\Module\Symfony\HttpKernelAssertionsTrait;
use Codeception\Module\Symfony\LoggerAssertionsTrait;
use Codeception\Module\Symfony\MailerAssertionsTrait;
use Codeception\Module\Symfony\MimeAssertionsTrait;
use Codeception\Module\Symfony\NotifierAssertionsTrait;
use Codeception\Module\Symfony\ParameterAssertionsTrait;
use Codeception\Module\Symfony\RouterAssertionsTrait;
use Codeception\Module\Symfony\SecurityAssertionsTrait;
use Codeception\Module\Symfony\ServicesAssertionsTrait;
use Codeception\Module\Symfony\SessionAssertionsTrait;
use Codeception\Module\Symfony\TimeAssertionsTrait;
use Codeception\Module\Symfony\TranslationAssertionsTrait;
use Codeception\Module\Symfony\TwigAssertionsTrait;
use Codeception\Module\Symfony\ValidatorAssertionsTrait;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use ReflectionException;
use Symfony\Bridge\Twig\DataCollector\TwigDataCollector;
use Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollector;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Notifier\DataCollector\NotificationDataCollector;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function class_exists;
use function codecept_root_dir;
use function count;
use function extension_loaded;
use function file_exists;
use function implode;
use function ini_get;
use function ini_set;
use function is_object;
use function sprintf;

/**
 * This module uses [Symfony's DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html)
 * and [HttpKernel Component](https://symfony.com/doc/current/components/http_kernel.html) to emulate requests and test response.
 *
 * * Access Symfony services through the dependency injection container: [`$I->grabService(...)`](#grabService)
 * * Use Doctrine to test against the database: `$I->seeInRepository(...)` - see [Doctrine Module](https://codeception.com/docs/modules/Doctrine)
 * * Assert that emails would have been sent: [`$I->seeEmailIsSent()`](#seeEmailIsSent)
 * * Tests are wrapped into Doctrine transaction to speed them up.
 * * Symfony Router can be cached between requests to speed up testing.
 *
 * ## Demo Project
 *
 * <https://github.com/Codeception/symfony-module-tests>
 *
 * ## Config
 *
 * ### Symfony 5.4 or higher
 *
 * * `app_path`: 'src' - Specify custom path to your app dir, where the kernel interface is located.
 * * `environment`: 'local' - Environment used for load kernel
 * * `kernel_class`: 'App\Kernel' - Kernel class name
 * * `em_service`: 'doctrine.orm.entity_manager' - Use the stated EntityManager to pair with Doctrine Module.
 * * `debug`: true - Turn on/off [debug mode](https://codeception.com/docs/Debugging)
 * * `cache_router`: 'false' - Enable router caching between tests in order to [increase performance](http://lakion.com/blog/how-did-we-speed-up-sylius-behat-suite-with-blackfire) (can have an impact on ajax requests sending via '$I->sendAjaxPostRequest()')
 * * `rebootable_client`: 'true' - Reboot client's kernel before each request
 * * `guard`: 'false' - Enable custom authentication system with guard (only for Symfony 5.4)
 * * `bootstrap`: 'false' - Enable the test environment setup with the tests/bootstrap.php file if it exists or with Symfony DotEnv otherwise. If false, it does nothing.
 * * `authenticator`: 'false' - Reboot client's kernel before each request (only for Symfony 6.0 or higher)
 *
 * #### Sample `Functional.suite.yml`
 *
 *     modules:
 *        enabled:
 *           - Symfony:
 *               app_path: 'src'
 *               environment: 'test'
 *
 *
 * ## Public Properties
 *
 * * kernel - HttpKernel instance
 * * client - current Crawler instance
 *
 * ## Parts
 *
 * * `services`: Includes methods related to the Symfony dependency injection container (DIC):
 *     * grabService
 *     * persistService
 *     * persistPermanentService
 *     * unpersistService
 *
 * See [WebDriver module](https://codeception.com/docs/modules/WebDriver#Loading-Parts-from-other-Modules)
 * for general information on how to load parts of a framework module.
 *
 * Usage example:
 *
 * ```yaml
 * actor: AcceptanceTester
 * modules:
 *     enabled:
 *         - Symfony:
 *             part: services
 *         - Doctrine:
 *             depends: Symfony
 *         - WebDriver:
 *             url: http://example.com
 *             browser: firefox
 * ```
 *
 * If you're using Symfony with Eloquent ORM (instead of Doctrine), you can load the [`ORM` part of Laravel module](https://codeception.com/docs/modules/Laravel#Parts)
 * in addition to Symfony module.
 */
class Symfony extends Framework implements DoctrineProvider, PartedModule
{
    use BrowserAssertionsTrait;
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

    public Kernel $kernel;

    /** @var SymfonyConnector|null */
    public ?AbstractBrowser $client = null;

    /**
     * @var array{
     *     app_path:string,
     *     kernel_class:string,
     *     environment:string,
     *     debug:bool,
     *     cache_router:bool,
     *     em_service:string,
     *     rebootable_client:bool,
     *     authenticator:bool,
     *     bootstrap:bool,
     *     guard:bool
     * }
     */
    public array $config = [
        'app_path'          => 'app',
        'kernel_class'      => 'App\\Kernel',
        'environment'       => 'test',
        'debug'             => true,
        'cache_router'      => false,
        'em_service'        => 'doctrine.orm.entity_manager',
        'rebootable_client' => true,
        'authenticator'     => false,
        'bootstrap'         => false,
        'guard'             => false,
    ];

    protected ?string $kernelClass = null;

    /**
     * Services that should be persistent permanently for all tests
     *
     * @var array<non-empty-string, object>
     */
    protected array $permanentServices = [];

    /**
     * Services that should be persistent during test execution between kernel reboots
     *
     * @var array<non-empty-string, object>
     */
    protected array $persistentServices = [];

    /** @var list<non-empty-string> */
    private array $internalDomainsCache = [];

    /** @return list<string> */
    public function _parts(): array
    {
        return ['services'];
    }

    public function _initialize(): void
    {
        $this->kernelClass = $this->getKernelClass();
        $this->setXdebugMaxNestingLevel(200);

        $kernelClass = $this->kernelClass;
        $kernel = new $kernelClass(
            $this->config['environment'],
            $this->config['debug']
        );

        if (!$kernel instanceof Kernel) {
            throw new \LogicException(sprintf('Kernel class "%s" must extend %s.', $kernelClass, Kernel::class));
        }

        $this->kernel = $kernel;

        if ($this->config['bootstrap']) {
            $this->bootstrapEnvironment();
        }

        $this->kernel->boot();

        if ($this->config['cache_router']) {
            $this->persistPermanentService('router');
        }
    }

    /**
     * Initialize new client instance before each test.
     */
    public function _before(TestInterface $test): void
    {
        $this->persistentServices = array_merge($this->persistentServices, $this->permanentServices);

        $this->client = new SymfonyConnector(
            $this->kernel,
            $this->persistentServices,
            $this->config['rebootable_client']
        );
    }

    /**
     * Update permanent services after each test.
     */
    public function _after(TestInterface $test): void
    {
        foreach (array_keys($this->permanentServices) as $serviceName) {
            $service = $this->getService($serviceName);
            if (is_object($service)) {
                $this->permanentServices[$serviceName] = $service;
            } else {
                unset($this->permanentServices[$serviceName]);
            }
        }
        $this->persistentServices = [];
        parent::_after($test);
    }

    /** @param array<string, string|bool> $settings */
    protected function onReconfigure(array $settings = []): void
    {
        parent::_beforeSuite($settings);
        $this->_initialize();
    }

    /**
     * Retrieve the Doctrine EntityManager.
     * EntityManager service is retrieved once and then reused.
     */
    public function _getEntityManager(): EntityManagerInterface
    {
        $emService = $this->config['em_service'];
        if ($emService === '') {
            throw new \LogicException('The "em_service" config option must be a non-empty string.');
        }

        if (!isset($this->permanentServices[$emService])) {
            $this->persistPermanentService($emService);
            $container = $this->_getContainer();
            foreach (
                ['doctrine', 'doctrine.orm.default_entity_manager', 'doctrine.dbal.default_connection'] as $service
            ) {
                if ($container->has($service)) {
                    $this->persistPermanentService($service);
                }
            }
        }

        /** @var EntityManagerInterface */
        return $this->permanentServices[$emService];
    }

    public function _getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();
        /** @var ContainerInterface $testContainer */
        $testContainer = $container->has('test.service_container') ? $container->get('test.service_container') : $container;
        return $testContainer;
    }

    protected function getClient(): SymfonyConnector
    {
        if ($this->client === null) {
            Assert::fail('Client is not initialized');
        }

        return $this->client;
    }

    /**
     * Find and require the Kernel class file.
     *
     * @return class-string<Kernel>
     * @throws ModuleRequireException|ReflectionException
     */
    protected function getKernelClass(): string
    {
        $kernelClass = $this->config['kernel_class'];
        $this->requireAdditionalAutoloader();

        if (class_exists($kernelClass)) {
            if (!is_subclass_of($kernelClass, Kernel::class)) {
                throw new \LogicException(sprintf('The "kernel_class" config option must be a valid class string extending Symfony\Component\HttpKernel\Kernel. "%s" given.', $kernelClass));
            }
            return $kernelClass;
        }

        $rootDir = codecept_root_dir();
        if (!is_string($rootDir)) {
            throw new \UnexpectedValueException('Root directory must be a string.');
        }
        $path = $rootDir . $this->config['app_path'];

        if (!file_exists($path)) {
            throw new ModuleRequireException(
                self::class,
                "Can't load Kernel from {$path}.\n"
                . 'Directory does not exist. Set `app_path` in your suite configuration to a valid application path.'
            );
        }

        $finder = new Finder();
        $finder->name('*Kernel.php')->depth('0')->in($path);

        foreach ($finder as $file) {
            include_once $file->getRealPath();
        }

        if (class_exists($kernelClass, false)) {
            if (!is_subclass_of($kernelClass, Kernel::class)) {
                throw new \LogicException(sprintf('The "kernel_class" config option must be a valid class string extending Symfony\Component\HttpKernel\Kernel. "%s" given.', $kernelClass));
            }
            return $kernelClass;
        }

        throw new ModuleRequireException(
            self::class,
            "Kernel class was not found at {$path}.\n"
            . 'Specify directory where file with Kernel class for your application is located with `app_path` parameter.'
        );
    }

    /**
     * @throws AssertionFailedError
     */
    protected function getProfile(): ?Profile
    {
        /** @var Profiler|null $profiler */
        $profiler = $this->getService('profiler');

        if ($profiler === null) {
            return null;
        }

        try {
            return $profiler->loadProfileFromResponse($this->getClient()->getResponse());
        } catch (BadMethodCallException) {
            Assert::fail('You must perform a request before using this method.');
        }
    }

    /**
     * Set the data that will be displayed when running a test with the `--debug` flag.
     */
    protected function debugResponse(mixed $url): void
    {
        parent::debugResponse($url);

        $profile = $this->getProfile();
        if ($profile === null) {
            return;
        }

        if ($profile->hasCollector(DataCollectorName::SECURITY->value)) {
            $collector = $profile->getCollector(DataCollectorName::SECURITY->value);
            if ($collector instanceof SecurityDataCollector) {
                $this->debugSecurityData($collector);
            }
        }

        if ($profile->hasCollector(DataCollectorName::MAILER->value)) {
            $collector = $profile->getCollector(DataCollectorName::MAILER->value);
            if ($collector instanceof MessageDataCollector) {
                $this->debugMailerData($collector);
            }
        }

        if ($profile->hasCollector(DataCollectorName::NOTIFIER->value)) {
            $collector = $profile->getCollector(DataCollectorName::NOTIFIER->value);
            if ($collector instanceof NotificationDataCollector) {
                $this->debugNotifierData($collector);
            }
        }

        if ($profile->hasCollector(DataCollectorName::TIME->value)) {
            $collector = $profile->getCollector(DataCollectorName::TIME->value);
            if ($collector instanceof TimeDataCollector) {
                $this->debugTimeData($collector);
            }
        }
    }

    /** @return list<non-empty-string> */
    protected function getInternalDomains(): array
    {
        if ($this->internalDomainsCache !== []) {
            return $this->internalDomainsCache;
        }

        $domains = [];

        foreach ($this->grabRouterService()->getRouteCollection() as $route) {
            if ($route->getHost() !== '') {
                $regex = $route->compile()->getHostRegex();
                if ($regex !== null && $regex !== '') {
                    $domains[] = $regex;
                }
            }
        }

        /** @var list<non-empty-string> */
        $this->internalDomainsCache = array_values(array_unique($domains));

        return $this->internalDomainsCache;
    }

    /**
     * Ensure Xdebug allows deep nesting.
     */
    private function setXdebugMaxNestingLevel(int $max): void
    {
        if (!extension_loaded('xdebug')) {
            return;
        }

        if ((int) ini_get('xdebug.max_nesting_level') < $max) {
            ini_set('xdebug.max_nesting_level', (string) $max);
        }
    }

    /**
     * Bootstrap environment via tests/bootstrap.php or Dotenv.
     */
    private function bootstrapEnvironment(): void
    {
        $bootstrapFile = $this->kernel->getProjectDir() . '/tests/bootstrap.php';

        if (file_exists($bootstrapFile)) {
            include_once $bootstrapFile;
            return;
        }

        $_ENV['APP_ENV'] = $this->config['environment'];
        (new Dotenv())->bootEnv('.env');
    }

    private function debugSecurityData(SecurityDataCollector $securityCollector): void
    {
        if (!$securityCollector->isAuthenticated()) {
            $this->debugSection('User', 'Anonymous');
            return;
        }

        $roles = $securityCollector->getRoles();
        if ($roles instanceof Data) {
            $roles = $roles->getValue(true);
        }

        $rolesStr = implode(',', array_map('strval', array_filter((array) $roles, 'is_scalar')));
        $this->debugSection('User', sprintf('%s [%s]', $securityCollector->getUser(), $rolesStr));
    }

    private function debugMailerData(MessageDataCollector $messageCollector): void
    {
        $count = count($messageCollector->getEvents()->getMessages());
        $this->debugSection('Emails', sprintf('%d sent', $count));
    }

    private function debugNotifierData(NotificationDataCollector $notificationCollector): void
    {
        $count = count($notificationCollector->getEvents()->getMessages());
        $this->debugSection('Notifications', sprintf('%d sent', $count));
    }

    private function debugTimeData(TimeDataCollector $timeCollector): void
    {
        $this->debugSection('Time', sprintf('%.2f ms', $timeCollector->getDuration()));
    }

    /**
     * Ensures autoloader loading of additional directories.
     * It is only required for CI jobs to run correctly.
     */
    private function requireAdditionalAutoloader(): void
    {
        $rootDir = codecept_root_dir();
        if (!is_string($rootDir)) {
            throw new \UnexpectedValueException('Root directory must be a string.');
        }
        $autoload = $rootDir . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        if (file_exists($autoload)) {
            include_once $autoload;
        }
    }

    /** @param non-empty-string $name */
    protected function updateClientPersistentService(string $name, ?object $service): void
    {
        if ($this->client instanceof SymfonyConnector) {
            if ($service === null) {
                unset($this->client->persistentServices[$name]);
            } else {
                $this->client->persistentServices[$name] = $service;
            }
        }
    }
}
