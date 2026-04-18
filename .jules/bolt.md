
## Performance Pattern: DOM Crawler Counting

For `Symfony\Component\DomCrawler\Crawler` objects (e.g., `$node` in assertion traits), calling the native `$node->count()` method directly is about 2x faster than using the global `count($node)` function. This bypasses PHP's `Countable` interface dispatch overhead and improves performance.
