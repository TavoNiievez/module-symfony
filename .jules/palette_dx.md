## 2024-04-16 - Replacing @phpstan-ignore with class_exists
**Learning:** Usage of `@phpstan-ignore` annotations is strictly prohibited throughout the codebase. The codebase prefers utilizing robust checks like `class_exists()` to make the code statically analyzable while preventing PHPStan errors, rather than ignoring them outright.
**Action:** Replace `@phpstan-ignore` suppression with `class_exists()` or `interface_exists()` checks to evaluate the availability of optional components (such as Notifier), thereby improving the type safety and adherence to the DX standard rules.
