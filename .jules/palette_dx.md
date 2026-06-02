## 2024-04-14 - Rename mystery meat parameters for clarity
**Learning:** Method parameters with vague, type-like names (e.g. `$mixed`, `$data`, `$value`) force developers to read the PHPDoc or function implementation to understand what is actually required. This increases cognitive load.
**Action:** Always rename vague parameter names to descriptive ones that indicate their purpose or expected domain object (e.g. `$mixed` -> `$entityOrClass`), strictly improving code discoverability.
## 2026-06-02 - Refactoring vague variable names
**Learning:** Renaming parameters in public methods introduces a BC break for consumers using PHP 8+ named arguments.
**Action:** Focus such DX refactoring strictly on private or protected methods where it is safe to do so.

## 2026-06-02 - Statically analyzable version checks
**Learning:** Using `version_compare` dynamically for runtime checks can trigger PHPStan errors (e.g., if.alwaysFalse) which might tempt developers to use .
**Action:** Replace dynamic version checks with statically analyzable feature checks, like `class_exists()`, to satisfy strict analysis without suppressions.

## 2026-06-02 - Statically analyzable version checks
**Learning:** Using version_compare dynamically for runtime checks can trigger PHPStan errors (e.g., if.alwaysFalse) which might tempt developers to use phpstan-ignore.
**Action:** Replace dynamic version checks with statically analyzable feature checks, like class_exists, to satisfy strict analysis without suppressions.
