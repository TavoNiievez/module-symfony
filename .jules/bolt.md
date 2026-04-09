## 2025-04-09 - O(1) Condition Checks instead of in_array() overhead
**Learning:** In heavily repeated loops, constructing small arrays (e.g., `['MOCKSESSID', 'REMEMBERME', $sessionName]`) just to check if a value exists via `in_array()` creates unnecessary memory allocation and lookup overhead. Direct O(1) `===` checks avoid this.
**Action:** Use strict equality combined with logical OR (`||`) for small sets of static value checks instead of `in_array()` with on-the-fly arrays.
