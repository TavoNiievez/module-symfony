## 2025-02-15 - Remove @phpstan-ignore with class_exists
**Learning:** Checking `Kernel::VERSION` for component availability triggers PHPStan errors because the version constant is fixed at analysis time.
**Action:** Use `class_exists()` or `interface_exists()` for optional components to ensure static analysis correctly infers code reachability without `@phpstan-ignore`.