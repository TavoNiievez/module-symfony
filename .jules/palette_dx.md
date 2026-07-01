## 2024-07-01 - Custom Domain Exceptions for Error Clarity
**Learning:** Using generic exceptions like `InvalidArgumentException` hides the root cause and context of errors, leading to higher cognitive load for developers trying to debug failures in testing environments.
**Action:** Always prefer creating custom, descriptively named domain exceptions (e.g., `InvalidSessionAttributeException`) that extend the appropriate base exception. This makes it instantly clear what went wrong and where.
