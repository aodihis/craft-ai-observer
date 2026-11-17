# 0003 — Target PHP 8.5 (the only supported runtime)

**Status:** Accepted
**Date:** 2026-11-17

## Context

The package started with a `^8.3` floor, inherited from `laravel/ai`'s own
requirement, on the theory that the library "must work in any Laravel app".
In practice it is only ever installed into Craft CMS 6, which runs PHP 8.5
locally and in DDEV, and that is the only runtime we test or support.

A floor below the runtime costs real money: the committed `composer.lock` has
to be resolved for the lowest version (`config.platform.php`), which pins
`symfony/*` and friends to older majors than we actually run, and CI has to
verify a version nobody uses. It also invites code that works on 8.3 and
silently skips 8.4/8.5 features we could rely on.

## Decision

PHP `^8.5` is the floor, the local runtime, and the CI runtime:

- `composer.json`: `"require": { "php": "^8.5" }`, `"config": { "platform": { "php": "8.5.0" } }`
  so the lock is resolved for the version we run.
- `mago.toml`: `php-version = "8.5"` (analysis target, unchanged).
- `.github/workflows/ci.yml`: a single PHP 8.5 job, not a matrix.
- `docs/rules/backend.md` environment section: PHP `^8.5`.

`laravel/ai` still declares `^8.3`; requiring more than a dependency is not a
conflict.

## Consequences

- 8.4/8.5 language features are fair game (`#[\Override]`, typed constants,
  property hooks in future) as long as the toolchain accepts them.
- Hosts below 8.5 cannot install the package. Accepted: Craft 6 requires 8.5.
- The floor must be changed in all four places together; changing one leaves
  the lock or CI disagreeing with the declaration.
- Revisit if the library is ever offered to non-Craft hosts on older PHP: that
  is a new ADR, not a quiet version bump.
