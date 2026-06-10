## 2024-05-18 - Improve Error Clarity with Custom Exceptions
**Learning:** Generic \InvalidArgumentException makes it harder to trace the root cause and handle errors specifically. Codeception codebase expects custom InvalidSessionAttributeException instead of generic InvalidArgumentException in SessionAssertionsTrait::seeSessionHasValues.
**Action:** Replace `\InvalidArgumentException` with a domain-specific `InvalidSessionAttributeException`.
