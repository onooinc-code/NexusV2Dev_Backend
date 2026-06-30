# Observation
- The project's current `composer.json` requires `"php": "^8.2"`, `"laravel/framework": "^11.31"`, and `"laravel/tinker": "^2.9"`.
- `composer show -a laravel/framework` with constraint `^13.0` reveals that Laravel 13 requires `php ^8.3` and has a conflict with older versions of `illuminate/support`.
- Running `composer update --dry-run` after bumping `laravel/framework` to `^13.0` results in a dependency conflict where `laravel/tinker ^2.9` is only compatible with `illuminate/support` up to `^12.0`.
- Running `composer update --dry-run` with all targeted dependencies set to `*` successfully resolves all dependencies, and reveals that `laravel/tinker` resolves to `v3.0.2`.
- Setting `"php": "^8.3"`, `"laravel/framework": "^13.0"`, and `"laravel/tinker": "^3.0"` while leaving all other constraints unchanged allows `composer update --dry-run` to complete without any conflicts.
- `phpunit/phpunit` resolves to `v11.5.55` which matches the existing `"^11.0.1"` constraint, though Laravel 13's own `require-dev` recommends `phpunit/phpunit ^11.5.50 || ^12.5.8 || ^13.0.3`.

# Logic Chain
1. To upgrade `laravel/framework` to `^13.0`, the PHP constraint must be bumped to `"^8.3"` because Laravel 13 enforces this minimum version.
2. The current constraint for `laravel/tinker` (`^2.9`) caps its compatibility at Laravel 12 (`illuminate/support ^12.0`). It must be bumped to `"^3.0"` to allow Laravel 13 (`illuminate/support ^13.0`).
3. All other dependencies listed in the scope (`laravel/horizon`, `laravel/reverb`, `laravel/sanctum`, `opcodesio/log-viewer`, `pusher/pusher-php-server`, `barryvdh/laravel-debugbar`, `laravel/boost`, `laravel/pail`, `laravel/pint`, `laravel/sail`, `laravel/telescope`, `nunomaduro/collision`, `phpunit/phpunit`) have existing constraints that successfully resolve to versions compatible with Laravel 13.
4. Therefore, the minimal required changes to `composer.json` are limited to `php`, `laravel/framework`, and `laravel/tinker`.

# Caveats
- While `phpunit/phpunit` passes resolution with its existing `^11.0.1` constraint, it might be beneficial to bump it to `"^13.0"` or `"^11.5.50"` to better align with the versions officially tested against Laravel 13.

# Conclusion
The `composer.json` update requires the following minimal changes to successfully resolve dependencies for Laravel 13:

```json
    "require": {
        "php": "^8.3",
        "laravel/framework": "^13.0",
        "laravel/tinker": "^3.0",
        ...
```
All other package constraints from the M1.1 scope can be left as they are, as they implicitly resolve to Laravel 13 compatible versions.

# Verification Method
1. Modify `composer.json` in the project root with the recommended bumps.
2. Run `composer update --dry-run`.
3. The command will complete successfully without dependency conflict errors, verifying the exact required changes.
