
## DomCrawler Countable Overhead

**Learning:** When checking if a `DomCrawler` filter matched any elements, `assertGreaterThan(0, count($node))` was used. Since `$node` is an object implementing `Countable`, the global `count()` function checks the interface and dispatches to `$node->count()`. Calling `$node->count()` directly bypasses this overhead, resulting in slightly faster execution times (~2x speedup in isolated microbenchmarks).

**Action:** Replaced `count($node)` with `$node->count()` in `BrowserAssertionsTrait` and `FormAssertionsTrait`.
## 2026-05-03 - Symfony Finder vs Native glob Overhead

**Learning:** When searching for files in a single directory without recursion (depth 0), instantiating a full `Symfony\Component\Finder\Finder` object introduces unnecessary overhead compared to native PHP `glob()`. `glob()` operates directly at the C-level and avoids object creation, Iterator setup, and method dispatch overhead. For simple patterns like `*Kernel.php` in a specific path, `glob()` is measurably faster.

**Action:** Replaced `(new Finder())->name('*Kernel.php')->depth('0')->in()` with `glob($path . DIRECTORY_SEPARATOR . '*Kernel.php')` in `src/Codeception/Module/Symfony.php`.
