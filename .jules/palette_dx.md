## 2024-05-18 - [Add Generics for Doctrine Repositories]
**Learning:** Returning `EntityRepository` without generics forces developers to add `@var` annotations or lose autocomplete and type safety.
**Action:** Adding `@template T of object` and updating return types dynamically `($entityOrClass is class-string<T> ? EntityRepository<T> : EntityRepository<object>)` allows IDEs and PHPStan to correctly infer the repository type when a class-string is provided to `grabRepository`.
