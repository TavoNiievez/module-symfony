
## DomCrawler Countable Overhead

**Learning:** When checking if a `DomCrawler` filter matched any elements, `assertGreaterThan(0, count($node))` was used. Since `$node` is an object implementing `Countable`, the global `count()` function checks the interface and dispatches to `$node->count()`. Calling `$node->count()` directly bypasses this overhead, resulting in slightly faster execution times (~2x speedup in isolated microbenchmarks).

**Action:** Replaced `count($node)` with `$node->count()` in `BrowserAssertionsTrait` and `FormAssertionsTrait`.

## Finder vs glob for Shallow Directory Searches

**Learning:** Using `Symfony\Component\Finder\Finder` for simple, single-directory file searches (depth 0) introduces unnecessary object instantiation and Iterator overhead. Native PHP `glob()` is significantly faster for this specific use case as it operates directly at the C level without the abstraction layers of the Finder component.

**Action:** Replaced `(new Finder())->name('*Kernel.php')->depth('0')->in($path)` with `glob($path . DIRECTORY_SEPARATOR . '*Kernel.php') ?: []` in `Symfony.php`'s `getKernelClass` method.
