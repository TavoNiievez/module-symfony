## 2024-04-14 - Rename mystery meat parameters for clarity
**Learning:** Method parameters with vague, type-like names (e.g. `$mixed`, `$data`, `$value`) force developers to read the PHPDoc or function implementation to understand what is actually required. This increases cognitive load.
**Action:** Always rename vague parameter names to descriptive ones that indicate their purpose or expected domain object (e.g. `$mixed` -> `$entityOrClass`), strictly improving code discoverability.
## 2024-05-26 - [Robust Feature Detection]
**Learning:** Suppressing static analysis errors (like `@phpstan-ignore if.alwaysFalse`) for framework version checks (`version_compare`) hides potential bugs and creates fragile code, as static analyzers cannot evaluate these checks effectively.
**Action:** Replace dynamic framework version checks with robust, statically analyzable feature detection (e.g., `!class_exists(\Symfony\Component\Notifier\Event\NotificationEvents::class)`) to natively satisfy PHPStan and improve code resilience without needing `@phpstan-ignore`.
