# Handoff: M1.1 Dependencies - Laravel 13 Upgrade Analysis

## 1. Observation
I created a test environment (`composer_test`) with the original `composer.json` and bumped `laravel/framework` to `^13.0`.
- Running `composer show laravel/framework 13.* --all` shows that Laravel 13 requires `php: ^8.3` and `phpunit/phpunit: ^11.5.50 || ^12.5.8 || ^13.0.3`.
- Bumping only `php` to `^8.3` and `laravel/framework` to `^13.0` resulted in `composer update --dry-run` failures due to `laravel/tinker` and `phpunit/phpunit` constraints not being resolvable.
- Changing constraints for all scoped dependencies to `*` resulted in a successful `composer update`.
- `laravel/tinker` was upgraded to `v3.0.2`. Inspecting `laravel/tinker` version 2.x shows it only supports up to `illuminate/console ^12.0`. Version 3.x is required for Laravel 13.
- `phpunit/phpunit` was upgraded to `13.2.1`. The original constraint `^11.0.1` allows up to `11.x`, which could technically resolve to `11.5.50` (supported by Laravel 13), but a bump to `^13.0` is recommended as Laravel 13 defaults to PHPUnit 13.
- Other scoped packages (e.g., `laravel/horizon`, `laravel/sail`, `nunomaduro/collision`, `barryvdh/laravel-debugbar`, etc.) have existing caret constraints (like `^5.46`, `^1.26`, `^8.1`, `^4.3`) that naturally allow resolution to their newer minor/patch versions which support Laravel 13.

## 2. Logic Chain
1. To upgrade `laravel/framework` to `^13.0`, PHP must be bumped to `^8.3` to satisfy the framework's direct requirement.
2. `laravel/tinker` requires a major version bump to `^3.0` because `^2.9` is strictly capped at Laravel 12.
3. `phpunit/phpunit` should be bumped to `^13.0` (or at least `^11.5.50`) because older 11.0.x versions do not support Laravel 13, and `^13.0` aligns with the latest ecosystem default for Laravel 13.
4. The remaining packages have loose enough constraints (`^`) that `composer update` will automatically pull the compatible newer minor versions without needing their strings changed in `composer.json` (e.g., `laravel/sail ^1.26` allows `1.63.0` which supports Laravel 13).

## 3. Caveats
- I did not test the actual application code, only the package resolution. Application code may have deprecations to address.
- `block-insecure` had to be temporarily disabled or ignored for testing, as there are current Packagist advisories on `laravel/framework` versions in this simulated environment. The actual upgrade might require adding specific advisories to `audit.ignore` if they are not yet patched.

## 4. Conclusion
To achieve the upgrade, modify `composer.json` with the following minimum constraint changes:
- `"php"`: `"^8.3"`
- `"laravel/framework"`: `"^13.0"`
- `"laravel/tinker"`: `"^3.0"`
- `"phpunit/phpunit"`: `"^13.0"` (or `^11.5.50`)

The other dependencies in `SCOPE.md` do not strictly require constraint changes in `composer.json` since their current caret (`^`) constraints already allow composer to resolve versions compatible with Laravel 13.

## 5. Verification Method
1. Apply the recommended constraints to `composer.json`.
2. Run `composer update --dry-run`.
3. It should complete without dependency conflicts, successfully resolving `laravel/framework` to `13.x`.
