## 2024-07-08 - Use domain-specific exceptions over generic exceptions
**Learning:** Generic exceptions (like \InvalidArgumentException) hide the context of the error, making it harder to debug, especially during testing environments.
**Action:** Always prefer creating explicit domain-specific exceptions (like InvalidSessionAttributeException) for specific, identifiable errors.
