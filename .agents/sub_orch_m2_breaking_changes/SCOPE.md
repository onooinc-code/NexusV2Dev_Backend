# Scope: Milestone 2 - Breaking Changes

## Architecture
- The application has been upgraded from Laravel 11 to Laravel 13 dependencies.
- We must fix any code breakages introduced by Laravel 12 and Laravel 13 upgrade paths.
- Goal: Boot the application without fatal errors and ensure basic functionality works (e.g. `php artisan about` and `php artisan route:list` complete successfully).

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | M2.1 | Run boot checks (e.g., `php artisan serve --no-reload` or `php artisan about`) to identify immediate fatal errors. Review upgrade guides for L12 and L13. | none | DONE |
| 2 | M2.2 | Fix identified breaking changes in `app/`, `config/`, `bootstrap/`, `routes/`, etc. | M2.1 | DONE |

## Interface Contracts
- Focus on getting the application to boot and CLI to function. Test suite fixes are for M3, although getting the application to boot will naturally fix many tests.
- Successful state: `php artisan route:list` runs cleanly without fatal errors.
