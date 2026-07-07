## 2024-07-07 - Custom Exception for Invalid Session Attributes
**Learning:** Generic `InvalidArgumentException`s with hardcoded messages hide the specific domain context of an error and make it difficult to catch or test specific error states.
**Action:** Created `InvalidSessionAttributeException` domain exception to encapsulate the error context when an invalid attribute type is given, reducing cognitive load and adhering to Palette's principles of clear errors.
