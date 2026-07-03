## 2024-05-14 - Custom Exceptions for Clearer Error Context
**Learning:** Generic exceptions like \InvalidArgumentException reduce code discoverability and increase cognitive load when reading test output or traces.
**Action:** Always create explicitly named custom domain exceptions (e.g., `InvalidSessionAttributeException`) to clearly communicate the failure context.
