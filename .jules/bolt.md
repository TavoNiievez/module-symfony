## 2024-06-17 - Initial Bolt File
**Learning:** This file tracks Bolt's crucial performance learnings to help optimize this specific codebase over time.
**Action:** Consult this list before proposing optimizations.
## 2024-06-17 - Avoid O(N) allocation with getMessages()
**Learning:** Calling `$events->getMessages()` in Symfony's `MessageEvents` or `NotificationEvents` creates and returns a new array every time.
**Action:** When you only need the count, or a specific item (like the last one), use `$events->getEvents()` instead. It returns the internal array directly (O(1) allocation) rather than allocating a new array containing all items (O(N) allocation).
