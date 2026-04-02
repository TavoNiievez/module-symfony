## 2024-05-16 - DX Log

**Learning:** Duck-typing with `method_exists` is a common pattern in legacy PHP code that can be replaced with the modern `\Stringable` interface to improve type safety and readability. Also, closures like `array_filter` can be replaced with direct loops with early breaks for performance improvements.

**Action:** Prefer explicit interfaces (`\Stringable`) over duck-typing methods (`method_exists`) for better type safety and DX. Replace closures with direct loops with early exits when closures just do boolean checks, improving performance and readability.