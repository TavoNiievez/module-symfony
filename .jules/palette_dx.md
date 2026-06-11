## 2023-10-27 - [Add Custom Exception to replace generic one]
**Learning:** [Generic \InvalidArgumentException thrown on type check failure instead of descriptive exception]
**Action:** [Create a custom `InvalidSessionAttributeException` class and use it in `SessionAssertionsTrait::seeSessionHasValues` when an invalid attribute name type is provided.]
