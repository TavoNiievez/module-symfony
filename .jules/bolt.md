
## DomCrawler Countable Overhead

**Learning:** When checking if a `DomCrawler` filter matched any elements, `assertGreaterThan(0, count($node))` was used. Since `$node` is an object implementing `Countable`, the global `count()` function checks the interface and dispatches to `$node->count()`. Calling `$node->count()` directly bypasses this overhead, resulting in slightly faster execution times (~2x speedup in isolated microbenchmarks).

**Action:** Replaced `count($node)` with `$node->count()` in `BrowserAssertionsTrait` and `FormAssertionsTrait`.
## 2024-05-18 - Optimize File Search with `glob()`
**Learning:** For simple, single-directory file searches (depth 0), using the native PHP `glob()` function is significantly faster than using the `Symfony\Component\Finder\Finder` component due to reduced object instantiation and iterator overhead. When checking for `*Kernel.php` files in a given app path, `glob()` provides a clean, native solution. Make sure to use the null coalescing operator (`?: []`) to safely handle `glob()` returning `false` on some file systems.
**Action:** Replaced `Symfony\Component\Finder\Finder` with `glob($path . DIRECTORY_SEPARATOR . '*Kernel.php') ?: []` in `getKernelClass()` within `src/Codeception/Module/Symfony.php` to improve performance. Unused `Finder` import was subsequently removed.
