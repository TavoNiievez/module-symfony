## 2024-05-24 - Custom Exceptions for better clarity
**Learning:** Generic exceptions like `\InvalidArgumentException` can hide the root cause and make debugging harder.
**Action:** Prefer creating explicitly named custom domain exceptions (e.g., `InvalidSessionAttributeException` extending `\InvalidArgumentException`) rather than throwing generic exceptions to clearly communicate the failure context and reduce cognitive load for developers.
