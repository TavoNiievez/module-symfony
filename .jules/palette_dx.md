## 2024-05-15 - Replace generic exceptions with descriptive domain exceptions
**Learning:** Generic \InvalidArgumentException makes it difficult to understand exactly what failed in testing code (e.g. invalid session attribute), especially without a stack trace. This increases cognitive load.
**Action:** Always prefer creating explicitly named custom exceptions (e.g. InvalidSessionAttributeException) rather than throwing generic exceptions to clearly communicate the failure context to other developers.
