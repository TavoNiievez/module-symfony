## 2024-06-07 - Add Custom Exception for Invalid Session Attribute Type
**Learning:** Generic `InvalidArgumentException` used for invalid types in `seeSessionHasValues` reduces error clarity, violating the principle of clear and specific errors.
**Action:** Created `InvalidSessionAttributeException` and used it instead, improving error specificity.
