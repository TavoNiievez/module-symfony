
## DomCrawler Countable Overhead

**Learning:** When checking if a `DomCrawler` filter matched any elements, `assertGreaterThan(0, count($node))` was used. Since `$node` is an object implementing `Countable`, the global `count()` function checks the interface and dispatches to `$node->count()`. Calling `$node->count()` directly bypasses this overhead, resulting in slightly faster execution times (~2x speedup in isolated microbenchmarks).

**Action:** Replaced `count($node)` with `$node->count()` in `BrowserAssertionsTrait` and `FormAssertionsTrait`.

## Finder vs glob Overhead

**Learning:** When searching for files in a single directory without recursion (depth 0), using `Symfony\Component\Finder\Finder` introduces unnecessary object instantiation and iteration overhead. Native `glob()` is significantly faster for these simple use cases, though it requires handling potential empty results correctly (e.g., `glob(...) ?: []`).

**Action:** Replaced `Finder` with `glob()` in `src/Codeception/Module/Symfony.php`'s `getKernelClass()` method.
