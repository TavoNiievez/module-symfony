## 2024-04-14 - Rename mystery meat parameters for clarity
**Learning:** Method parameters with vague, type-like names (e.g. `$mixed`, `$data`, `$value`) force developers to read the PHPDoc or function implementation to understand what is actually required. This increases cognitive load.
**Action:** Always rename vague parameter names to descriptive ones that indicate their purpose or expected domain object (e.g. `$mixed` -> `$entityOrClass`), strictly improving code discoverability.

## 2024-05-10 - [Removing @phpstan-ignore with Robust Checks]
**Learning:** Usage of `@phpstan-ignore` hides potential bugs and violates strict static analysis standards, especially when dynamic runtime versions (e.g., `Kernel::VERSION`) are evaluated incorrectly by PHPStan during static analysis.
**Action:** Replace `version_compare` or dynamic constants with robust, statically analyzable checks like `class_exists()` or `method_exists()` to satisfy PHPStan and enforce strict type safety without suppression.
