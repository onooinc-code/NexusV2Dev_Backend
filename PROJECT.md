# Project: Nexus Backend Laravel Upgrade
# Scope: Upgrade from Laravel 11 to 13

## Architecture
- Upgrading `laravel/framework` from `^11.31` to `^13.0`.
- Dependencies: Need to update horizon, reverb, sanctum, tinker, log-viewer, debugbar, faker, boost, pail, pint, sail, telescope, mockery, collision, phpunit.
- PHP version might need updating to `^8.3` or `^8.4` based on Laravel 13 requirements. (We'll check the minimum PHP version).
- Test framework: PHPUnit

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | Dependencies | Update composer.json to Laravel 13.x and run composer update. Fix dependency conflicts. | none | DONE |
| 2 | Breaking Changes | Review Laravel 12 and 13 upgrade guides. Fix immediate fatal errors and boot application. | M1 | DONE |
| 3 | Test Suite | Run tests via `php artisan test`. Fix failing tests. | M2 | IN_PROGRESS |

## Interface Contracts
### Framework ↔ App
- Laravel 13 APIs will replace Laravel 11 APIs where deprecated. No external interface contracts are changed, only internal usage of Laravel's container, routing, eloquent, etc.

## Code Layout
- Standard Laravel layout: `app/`, `config/`, `routes/`, `tests/`.
