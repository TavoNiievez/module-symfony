## 2024-05-24 - [Enforce type safety for debugging data collectors]
**Learning:** The `debugCollector` method was previously accepting primitive strings, leading to potential typos and missing IDE autocompletion.
**Action:** Replaced the primitive string parameter with the `DataCollectorName` Enum to enforce type safety, improve DX, and rely on PHPStan/IDE for validation.
