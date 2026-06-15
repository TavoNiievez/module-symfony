## 2024-06-15 - [Refactor vague variable names]
**Learning:** Avoid using the variable name `$function` for string variables that represent a function name, as it can cause cognitive confusion with PHP's `callable` pseudo-type or anonymous functions. Prefer context-specific names like `$callingFunction`.
**Action:** Rename `$function` to `$callingFunction` in HttpClientAssertionsTrait.
