## 2024-04-14 - Rename mystery meat parameters for clarity
**Learning:** Method parameters with vague, type-like names (e.g. `$mixed`, `$data`, `$value`) force developers to read the PHPDoc or function implementation to understand what is actually required. This increases cognitive load.
**Action:** Always rename vague parameter names to descriptive ones that indicate their purpose or expected domain object (e.g. `$mixed` -> `$entityOrClass`), strictly improving code discoverability.
## 2026-06-05 - Native Return Type Hints for Internal Methods
**Learning:** The PHP docblock `@return Type` cannot replace the benefits of native PHP return type hints for internal consistency and type safety. Static analysis via PHPStan effectively catches type constraint violations when native hints are applied.
**Action:** Proactively replace generic docblock return types with strict, native PHP return type hints (e.g. `: Security`) where appropriate, particularly on internal and protected methods where the BC impact is minimized.
