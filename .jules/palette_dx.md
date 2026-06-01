## 2024-04-14 - Rename mystery meat parameters for clarity
**Learning:** Method parameters with vague, type-like names (e.g. `$mixed`, `$data`, `$value`) force developers to read the PHPDoc or function implementation to understand what is actually required. This increases cognitive load.
**Action:** Always rename vague parameter names to descriptive ones that indicate their purpose or expected domain object (e.g. `$mixed` -> `$entityOrClass`), strictly improving code discoverability.

## 2024-06-01 - Replace dynamic version checks for static analysis
**Learning:** `version_compare(Kernel::VERSION, ...)` cannot always be statically analyzed and can cause PHPStan to flag `if.alwaysFalse` if it believes the condition can never be met based on minimum dependencies, forcing developers to use prohibited `@phpstan-ignore` comments.
**Action:** Replace dynamic version checks with statically analyzable alternatives, like `class_exists()` for classes or `method_exists()` for features introduced in specific versions.
