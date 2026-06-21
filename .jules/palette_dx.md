## 2026-06-09 - DX: Avoid 'callable'-like Variable Names
**Learning:** Using variables named `$function` for strings representing method or function names causes cognitive confusion with PHP's `callable` pseudo-type or anonymous functions.
**Action:** Always rename vague variables like `$function` to context-specific names like `$callingFunction` to improve code clarity and discoverability.
