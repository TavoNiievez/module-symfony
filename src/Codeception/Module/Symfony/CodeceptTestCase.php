<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * @experimental
 *
 * This class is intended to serve as an alternative to Symfony's WebTestCase,
 * with the goal of using the module-symfony directly with PHPUnit (without Codeception in between).
 * It is marked as experimental until sufficient feedback is received about its correct functioning in real applications.
 *
 * This means that its API may change even between minor versions of this module while it is marked as experimental.
 */
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

    protected ?KernelBrowser $client = null;
    protected ?KernelInterface $kernel = null;
    protected bool $profilerEnabled = true;

    /** @var array<string, bool> */
    protected array $config = ['guard' => false, 'authenticator' => false];

    protected function setUp(): void
    {
        $this->kernel = $this->createKernel();
        $this->kernel->boot();

        $container = $this->_getContainer();

        if ($container->has('doctrine.orm.entity_manager')) {
            /** @var EntityManagerInterface $em */
            $em = $container->get('doctrine.orm.entity_manager');
            $this->setUpDatabase($em);
        }

        $testClient = $container->has('test.client') ? $container->get('test.client') : null;
        if ($testClient instanceof KernelBrowser) {
            $this->client = $testClient;
        } else {
            if ($this->kernel === null) {
                // Should never happen since createKernel returns KernelInterface, but satisfies PHPStan
                throw new RuntimeException('The kernel is not initialized.');
            }
            $this->client = new KernelBrowser($this->kernel);
        }

        if ($this->profilerEnabled) {
            $this->client->enableProfiler();
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->kernel)) {
            $this->kernel->shutdown();
        }

        $this->restoreErrorHandler();

        $this->client = null;
        $this->kernel = null;

        parent::tearDown();
    }

    private function restoreErrorHandler(): void
    {
        if (!class_exists(ErrorHandler::class)) {
            return;
        }

        $exceptionHandler = set_exception_handler(null);
        restore_exception_handler();
        if (is_array($exceptionHandler) && $exceptionHandler[0] instanceof ErrorHandler) {
            restore_exception_handler();
        }

        $errorHandler = set_error_handler(null);
        restore_error_handler();
        if (is_array($errorHandler) && $errorHandler[0] instanceof ErrorHandler) {
            restore_error_handler();
        }
    }

    protected function createKernel(): KernelInterface
    {
        $kernelClass = $this->getKernelClass();

        $environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        if (!is_scalar($environment)) {
            $environment = 'test';
        }

        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;
        if (!is_bool($debug)) {
            $debug = is_scalar($debug)
                ? filter_var((string) $debug, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true
                : true;
        }

        /** @var KernelInterface $kernel */
        $kernel = new $kernelClass((string) $environment, $debug);

        return $kernel;
    }

    protected function getKernelClass(): string
    {
        if (!isset($_SERVER['KERNEL_CLASS']) && !isset($_ENV['KERNEL_CLASS'])) {
            throw new LogicException(sprintf(
                'You must set the KERNEL_CLASS environment variable in phpunit.xml or override %1$s::createKernel() / %1$s::getKernelClass().',
                static::class,
            ));
        }

        $class = $_ENV['KERNEL_CLASS'] ?? $_SERVER['KERNEL_CLASS'];

        if (!is_string($class) || !class_exists($class)) {
            throw new RuntimeException(sprintf(
                'Class "%s" doesn\'t exist or cannot be autoloaded. Check KERNEL_CLASS or override %s::createKernel().',
                is_scalar($class) ? (string) $class : gettype($class),
                static::class,
            ));
        }

        return $class;
    }

    protected function setUpDatabase(EntityManagerInterface $em): void
    {
        // Override this method to perform database setup
    }

    protected function getClient(): KernelBrowser
    {
        if ($this->client === null) {
            throw new RuntimeException(sprintf('The client is not initialized. Did you forget to call parent::setUp() in %s?', static::class));
        }

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
        $client = $this->getClient();
        $profile = $client->getProfile();

        if ($profile instanceof Profile) {
            return $profile;
        }

        try {
            $response = $client->getResponse();
            $request = $client->getRequest();
        } catch (BadMethodCallException) {
            return null;
        }

        if ($cachedProfile = $this->getProfileFromCache($response)) {
            return $cachedProfile;
        }

        $container = $this->_getContainer();
        if (!$container->has('profiler')) {
            return null;
        }

        /** @var Profiler $profiler */
        $profiler = $container->get('profiler');
        $profile = $profiler->collect($request, $response);

        if ($profile instanceof Profile) {
            $this->cacheProfile($response, $profile);
            return $profile;
        }

        return null;
    }
}
