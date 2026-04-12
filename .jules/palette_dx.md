## 2026-04-12 - Enforce Type Safety for Internal Debug Collectors
**Learning:** Method parameters accepting generic primitive strings (like `$name` for data collectors) bypass IDE autocompletion and static analysis, leading to "mystery meat" inputs.
**Action:** Replaced the primitive string parameter with a strict `DataCollectorName` Enum in `Symfony::debugCollector()`, extracting its `->value` internally. This enforces type safety at the method boundary while remaining transparent to the underlying Profile API.
