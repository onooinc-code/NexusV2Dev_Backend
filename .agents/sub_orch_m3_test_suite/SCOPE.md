# Scope: Milestone 3 - Test Suite

## Architecture
- The application has been upgraded to Laravel 13 and boots successfully.
- Tests in the `tests/` directory must pass to satisfy the user's acceptance criteria.
- Test runner: `php artisan test` or `./vendor/bin/phpunit`.

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | M3.1 | Analyze failing tests and categorize them (e.g. PHPUnit strictness, Policies, Routes). | none | DONE |
| 2 | M3.2.1 | Fix Policy type errors in tests. | M3.1 | PLANNED |
| 3 | M3.2.2 | Fix AI model provider_id migration changes in tests. | M3.1 | PLANNED |
| 4 | M3.2.3 | Fix PHPUnit 12 assertCount strictness issues in tests. | M3.1 | PLANNED |
| 5 | M3.2.4 | Fix missing legacy routes issues in tests. | M3.1 | PLANNED |
| 6 | M3.2.5 | Fix removed Console\Kernel issues in tests. | M3.1 | PLANNED |
| 7 | M3.3 | Run final `php artisan test` to verify 100% pass rate. | M3.2.1-M3.2.5 | PLANNED |

## Interface Contracts
- Must not change the user-facing functionality, only fix bugs introduced by the upgrade or adjust tests if the framework behavior has changed in a way that requires test updates.
- Successful state: `php artisan test` passes with 100% success and no fatal errors.
