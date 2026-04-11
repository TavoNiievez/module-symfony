## 2024-04-11 - [Use class_exists over version_compare for optional dependencies]
**Learning:** Checking class/interface existence instead of using version constraints allows static analysis to correctly inference code reachability without relying on prohibited @phpstan-ignore annotations.
**Action:** Always prefer `class_exists()` or `interface_exists()` checks over `version_compare()` when checking for optional Symfony components to avoid PHPStan errors and improve type safety.
