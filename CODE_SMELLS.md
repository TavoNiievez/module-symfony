# Code Smells and Suspicious Code in Symfony Module

## 1. `src/Codeception/Module/Symfony.php`

### 1.1 `_after` implementation (Service Cleanup)
```php
public function _after(TestInterface $test): void
{
    foreach ($this->permanentServices as $serviceName => $_) {
        $service = $this->getService($serviceName);
        if (is_object($service)) {
            $this->permanentServices[$serviceName] = $service;
        } else {
            unset($this->permanentServices[$serviceName]);
        }
    }
    // ...
}
```
**Issue:** `_after` updates the `$this->permanentServices` from the container, but it's not clear why a permanent service needs to be updated with whatever is currently in the container at the end of a test. If a service is permanent, replacing it with a potentially test-mutated instance could lead to test leakage.

### 1.2 `getKernelClass` Path Resolution and File Inclusion
```php
$expectedKernelPath = $path . DIRECTORY_SEPARATOR . 'Kernel.php';
if (file_exists($expectedKernelPath)) {
    include_once $expectedKernelPath;
} else {
    foreach ((new Finder())->name('*Kernel.php')->depth('0')->in($path) as $file) {
        include_once $file->getRealPath();
    }
}
```
**Issue:** Using `include_once` on all files matching `*Kernel.php` just to find the kernel class is a massive hack. It pollutes the environment and autoloading should handle class loading, not brute-force file inclusion.

### 1.3 `debugCollector` Type Check Hack
```php
$collector = $profile->getCollector($name);
match (true) {
    $collector instanceof SecurityDataCollector => $this->debugSecurityData($collector),
    $collector instanceof MessageDataCollector => $this->debugMailerData($collector),
    $collector instanceof NotificationDataCollector => $this->debugNotifierData($collector),
    $collector instanceof TimeDataCollector => $this->debugTimeData($collector),
    default => null,
};
```
**Issue:** While it uses `match (true)`, this is an anti-pattern. If the collector was fetched by name (`$name`), its type should be known or it should be mapped explicitly instead of sequentially checking `instanceof` on the resulting object.

### 1.4 `requireAdditionalAutoloader`
```php
private function requireAdditionalAutoloader(): void
{
    $rootDir  = codecept_root_dir();
    $autoload = $rootDir . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    if (file_exists($autoload)) {
        include_once $autoload;
    }
}
```
**Issue:** Explicitly requiring the `vendor/autoload.php` from the project root inside a library module is a very suspect workaround ("It is only required for CI jobs to run correctly"). The test runner (Codeception) should already be handling autoloading correctly.

## 2. `src/Codeception/Lib/Connector/Symfony.php`

### 2.1 `rebootKernel` and Exception Swallowing
```php
foreach ($this->persistentServices as $name => $service) {
    try {
        $this->container->set($name, $service);
    } catch (InvalidArgumentException $e) {
        if (function_exists('codecept_debug')) {
            codecept_debug("[Symfony] Can't set persistent service {$name}: {$e->getMessage()}");
        }
    }
}
```
**Issue:** Silently swallowing `InvalidArgumentException` when setting persistent services in the container. If a service cannot be set, the test will likely fail with cryptic errors later, or run with the wrong service instance.

### 2.2 `ensureKernelShutdown` Redundant Boot
```php
protected function ensureKernelShutdown(): void
{
    $this->kernel->boot();
    $this->kernel->shutdown();
}
```
**Issue:** Booting the kernel just to shut it down is extremely inefficient and suspicious. If the kernel isn't booted, shutting it down should be a no-op, not require booting first. Memory notes that a redundant `boot()` call was supposedly removed but it is still present in the file!

### 2.3 `persistDoctrineConnections` Reflection Hack
```php
private function persistDoctrineConnections(): void
{
    (function (): void {
        if (property_exists($this, 'parameters') && is_array($this->parameters)) {
            unset($this->parameters['doctrine.connections']);
        }
    })->call($this->kernel->getContainer());
}
```
**Issue:** Using `Closure::bind` (`->call()`) to forcefully hack into the protected/private properties (`parameters`) of the Symfony DI Container to unset `doctrine.connections` is a huge workaround and heavily tightly-coupled to the internal implementation of the Symfony Container.

## 3. `src/Codeception/Module/Symfony/HttpClientAssertionsTrait.php`

### 3.1 `extractValue` Duck Typing
```php
private function extractValue(mixed $value): mixed
{
    return match (true) {
        $value instanceof Data => $value->getValue(true),
        is_object($value) && method_exists($value, 'getValue') => $value->getValue(true),
        is_object($value) && method_exists($value, '__toString') => (string) $value,
        default => $value,
    };
}
```
**Issue:** The use of `is_object($value) && method_exists($value, 'getValue')` is a workaround for dealing with mixed data types (likely due to Symfony Profiler/VarDumper serialization) instead of strictly typing or knowing the expected data structure.

## 4. `src/Codeception/Module/Symfony/EventsAssertionsTrait.php`

### 4.1 Missing Type Information Fallback
```php
$listenerName = match (true) {
    is_array($listener) && isset($listener[0]) => is_string($listener[0]) ? $listener[0] : (is_object($listener[0]) ? $listener[0]::class : 'array'),
    is_object($listener) => $listener::class,
    is_string($listener) => $listener,
    default => get_debug_type($listener),
};
```
**Issue:** Complex and hard-to-read dynamic type resolution for listener names. It looks like a workaround to support multiple undocumented listener formats (arrays with string class names, arrays with object instances, plain objects, plain strings).

## 5. `src/Codeception/Module/Symfony/RouterAssertionsTrait.php`

### 5.1 O(N) Route Searching
```php
private function findRouteByActionOrFail(string $action): string
{
    foreach ($this->grabRouterService()->getRouteCollection()->all() as $name => $route) {
        $ctrl = $route->getDefault('_controller');
        if (is_string($ctrl) && str_ends_with($ctrl, $action)) {
            return $name;
        }
    }
    Assert::fail(sprintf("Action '%s' does not exist.", $action));
}
```
**Issue:** Searching for a route by iterating through all routes in the application on every call (`O(N)` complexity). This is inefficient. Memory notes mention an optimization for this trait using a cached map (`$this->cachedActionMap`) that is currently missing from the code.

## 6. `tests/_app/TestKernel.php`

### 6.1 Unconventional Kernel File Inclusion
```php
require_once __DIR__ . '/Security/SecurityBundleSecurityAlias.php';
```
**Issue:** Directly using `require_once` outside the autoloader context at the top of the test kernel file. This is a workaround, likely to ensure that a test-specific alias/class is loaded when the kernel is instanced.
