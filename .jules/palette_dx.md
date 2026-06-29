## 2024-06-29 - [Custom Exception for Session Attribute Assertion]
**Learning:** `InvalidArgumentException` was being thrown with a specific error message directly inside `SessionAssertionsTrait::seeSessionHasValues`. A custom exception `InvalidSessionAttributeException` makes it much clearer why this particular exception is thrown in testing environments when an invalid session attribute name type is provided. This reduces cognitive load.
**Action:** Use custom Exceptions with descriptive names and encapsulate error messages within them to replace generic generic ones.
