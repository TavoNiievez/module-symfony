## 2024-05-24 - Optimize O(N) array allocation in Symfony Data Collectors
**Learning:** Calling `getMessages()` on Symfony's `MessageEvents` or `NotificationEvents` triggers an iteration over all events to construct a new array of messages. When you only need to retrieve a specific event or count them, this creates unnecessary O(N) memory allocation and overhead.
**Action:** Use `getEvents()` to access the internal array directly for O(1) lookups or operations that don't strictly require an array of mapped messages.
