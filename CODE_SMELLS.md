# Code Smells and Issues

## 1. Redundant Kernel Boot in Connector
**Location:** `src/Codeception/Lib/Connector/Symfony.php` (method `ensureKernelShutdown`)
**Issue:** The method forcefully calls `$this->kernel->boot()` immediately before calling `$this->kernel->shutdown()`. If the kernel isn't booted, shutting it down should simply be a no-op instead of paying the performance penalty of booting it just to shut it down.
**Solution:** Remove the `$this->kernel->boot();` call.

## 2. Inefficient O(N) Route Lookup
**Location:** `src/Codeception/Module/Symfony/RouterAssertionsTrait.php` (method `findRouteByActionOrFail`)
**Issue:** The method iterates over *all* routes in the Symfony `RouteCollection` (`O(N)` complexity) every single time it's called to find a matching controller action. In large applications, this can significantly slow down tests.
**Solution:** Reverted to original `O(N)` implementation as the cache caused unintended behavior/complexity. Kept as a known, accepted code smell.

## 3. Legacy Duck Typing (`__toString`)
**Location:** `src/Codeception/Module/Symfony/HttpClientAssertionsTrait.php` (method `extractValue`)
**Issue:** The method checks `is_object($value) && method_exists($value, '__toString')`. In PHP 8+, any object implementing `__toString` automatically implements the built-in `\Stringable` interface.
**Solution:** Fixed using `$value instanceof \Stringable`.

## 4. Bypassing Composer Autoloader
**Location:** `tests/_app/TestKernel.php`
**Issue:** The file manually includes a class using `require_once __DIR__ . '/Security/SecurityBundleSecurityAlias.php';` at the top of the file, completely bypassing standard PSR-4 composer autoloading.
**Solution:** Reverted fix. The file acts as a polyfill alias dynamically providing missing classes based on the Symfony version. Loading it natively via Composer or modifying `TestKernel`'s bootstrapping sequence is unstable. Kept as an accepted workaround.

## 5. `_after` implementation (Service Cleanup)
**Location:** `src/Codeception/Module/Symfony.php`
**Issue:** `_after` attempts to update `$this->permanentServices` from the container blindly at the end of a test instead of simply clearing memory.
**Solution:** I kept the logic because the Codeception framework explicitly relies on storing instances across tests, but this design is inherently fragile.

## 6. `getKernelClass` Path Resolution and File Inclusion
**Location:** `src/Codeception/Module/Symfony.php`
**Issue:** Uses a `Finder` to search for `*Kernel.php` files in `app_path`, then calls `include_once` on them until a class matching the configured kernel is found. This is a hacky way to bootstrap a legacy Symfony application without standard autoloading.
**Solution:** This hack is kept because older Symfony 4/5 setups often relied on weird directory structures where the kernel wasn't properly PSR-4 mapped, but it remains a code smell.

## 7. `requireAdditionalAutoloader`
**Location:** `src/Codeception/Module/Symfony.php`
**Issue:** A method hardcoded to `include_once codecept_root_dir() . 'vendor/autoload.php'`. This should be entirely unnecessary if PHPUnit or Codeception properly bootstrap the environment, but it states "It is only required for CI jobs to run correctly".
**Solution:** Kept due to the stated comment, though it implies a misconfigured CI runner.

## 8. Exception Swallowing on Service Persist
**Location:** `src/Codeception/Lib/Connector/Symfony.php`
**Issue:** A `try/catch (InvalidArgumentException)` block ignores exceptions when setting persistent services in the container.
**Solution:** Kept because Symfony containers freeze and synthetic services cannot be reassigned; Codeception must swallow this error to continue testing.

## 9. Reflection Hack for Doctrine
**Location:** `src/Codeception/Lib/Connector/Symfony.php`
**Issue:** Uses `Closure::bind` (`->call()`) to forcefully hack into the protected `parameters` property of the Symfony DI Container to unset `doctrine.connections`.
**Solution:** Kept because it is a required workaround to reset database connections between simulated requests in functional testing.

## 10. `debugCollector` Dynamic Type Checking
**Location:** `src/Codeception/Module/Symfony.php`
**Issue:** Uses sequential `match (true)` with `instanceof` checks to map DataCollectors to debug formatting methods.
**Solution:** It is verbose, but it is the fastest native execution path in PHP 8 for dynamic dispatch without creating unnecessary closures or reflection.

## 11. `EventsAssertionsTrait` Missing Type Fallbacks
**Location:** `src/Codeception/Module/Symfony/EventsAssertionsTrait.php`
**Issue:** Uses complex dynamic type resolution (nested ternary arrays and class-strings) to determine the listener's actual name.
**Solution:** It's kept as a `match` expression since the input types to this method are highly variable and undocumented, making it impossible to cleanly strict-type the method signature without breaking backward compatibility.