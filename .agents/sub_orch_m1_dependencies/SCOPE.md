# Scope: Milestone 1 - Dependencies

## Architecture
- Upgrading `laravel/framework` from `^11.31` to `^13.0`.
- Requires modifying `composer.json` and running `composer update`.
- May require bumping PHP version constraint to `^8.3` or `^8.4` (check Laravel 13 requirements).
- We must also update `laravel/horizon`, `laravel/reverb`, `laravel/sanctum`, `laravel/tinker`, `opcodesio/log-viewer`, `pusher/pusher-php-server`, `barryvdh/laravel-debugbar`, `laravel/boost`, `laravel/pail`, `laravel/pint`, `laravel/sail`, `laravel/telescope`, `nunomaduro/collision`, `phpunit/phpunit` to compatible versions for Laravel 13.

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | M1.1 | Discover Laravel 13 requirements and update composer.json constraints. | none | DONE |
| 2 | M1.2 | Execute `composer update` and resolve any conflicts. | M1.1 | DONE |

## Interface Contracts
- Must not change the rest of the application code yet. Focus only on composer.json.
- Successful state: `composer update` completes without errors, and `vendor/` folder is populated with Laravel 13.
