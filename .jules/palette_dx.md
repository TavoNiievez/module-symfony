## 2024-05-18 - [Return Types for Internal Helpers]
**Learning:** Legacy traits often rely on `@return` PHPDoc annotations instead of strict PHP return types, which reduces IDE autocompletion confidence and type safety.
**Action:** When finding methods with only `@return` hints (especially for classes like `Security`), enforce native strict return types (e.g. `grabSecurityService(): Security`) to let PHP and the IDE enforce guarantees without manual tracking.
