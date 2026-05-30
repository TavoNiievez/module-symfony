## 2024-04-14 - Rename mystery meat parameters for clarity
**Learning:** Method parameters with vague, type-like names (e.g. `$mixed`, `$data`, `$value`) force developers to read the PHPDoc or function implementation to understand what is actually required. This increases cognitive load.
**Action:** Always rename vague parameter names to descriptive ones that indicate their purpose or expected domain object (e.g. `$mixed` -> `$entityOrClass`), strictly improving code discoverability.

## 2026-05-30 - Replace dynamic version checks with static checks
**Learning:** Dynamic version checks like `version_compare(Kernel::VERSION, ...)` combined with `@phpstan-ignore` suppress static analysis and hide potential type errors. The codebase guidelines strictly prohibit `@phpstan-ignore`.
**Action:** Replace dynamic version checks with robust, statically analyzable checks like `class_exists()` or `method_exists()` to satisfy PHPStan natively without suppression.
