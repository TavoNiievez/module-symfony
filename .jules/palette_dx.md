## 2024-07-04 - Type Safety for Exception Handling
**Learning:** Custom domain exceptions improve DX by reducing cognitive load when catching specific errors, and preventing developers from having to parse string messages of generic exceptions.
**Action:** Replace generic InvalidArgumentExceptions with explicitly named exceptions that subclass them.
