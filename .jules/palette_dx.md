## 2024-06-15 - [Refactor vague variable names]
**Learning:** Avoid using the variable name `$function` for string variables that represent a function name, as it can cause cognitive confusion with PHP's `callable` pseudo-type or anonymous functions. Prefer context-specific names like `$callingFunction`.
**Action:** Rename `$function` to `$callingFunction` in HttpClientAssertionsTrait.

## 2024-06-17 - [Custom Exception for better DX]
**Learning:** Generic exceptions like `InvalidArgumentException` make it hard to pinpoint the exact failure in tests. Replacing them with context-specific exceptions like `InvalidSessionAttributeException` reduces debugging time and cognitive load.
**Action:** When a generic exception is thrown to indicate a specific domain error (e.g. invalid type for a session attribute), introduce a well-named custom exception extending the generic one.
