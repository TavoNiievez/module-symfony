## 2024-05-18 - Improve Error Clarity with Custom Exceptions
**Learning:** The `SessionAssertionsTrait::seeSessionHasValues` method throws a generic `InvalidArgumentException` when an invalid attribute name type is provided. Creating a custom domain exception like `InvalidSessionAttributeException` improves clarity in testing environments, clearly communicating the failure context.
**Action:** Create a custom exception in `src/Codeception/Exception/` and use it instead of generic exceptions to reduce cognitive load.
