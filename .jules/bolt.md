## Codebase-Specific Performance Anti-Pattern: Repetitive DI Container Lookups in Assertion Loops

**Learning:** The `seeSessionHasValues` method inside `SessionAssertionsTrait` iterated through an array of bindings and called `seeInSession` for each item. `seeInSession` in turn called `getCurrentSession()`, which triggers a Service Container check (`$this->_getContainer()->has('session')`) or Service retrieval on every iteration. This is O(N) container lookups for an array of size N.
**Action:** Replaced repetitive lookups by pulling the session object once before the loop and inlining the assertions (`$session->has` and `$session->get`). This optimization pattern should be applied wherever arrays are validated against Symfony services or components that can be retrieved once.
