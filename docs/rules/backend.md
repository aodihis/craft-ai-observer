# Rules — Backend (craft-ai-observer)

**Status:** Accepted
**Date:** 2026-11-16

## Environment

- PHP `^8.3` (matches `laravel/ai` requirement; Craft 6 / DDEV run 8.5).
- Depends only on what is needed to listen: `illuminate/events` (or the
  `laravel/ai` event classes, which come free with Craft 6), `php 8.3+`.
- No dependency on `craftcms/cms`. The library must work in any Laravel
  app that uses `laravel/ai`.

## Code rules

1. PSR-4 namespace: `CraftAiObserver\...`
2. Listeners must not throw: wrap adapter dispatch in try/catch and log via
   `Psr\Log\LoggerInterface` (injected, never `Log::` facades at package
   level).
3. No database tables, no migrations, no queues (adapters may queue their
   flushes; the library hands them serializable records).
4. Configuration through the composer package's config file with safe
   defaults; plugins may prepend their own config.
5. Records are value objects (readonly), PSR-12, strict_types, typed.
6. Tests: Pest or PHPUnit, focused on TreeBuilder and event mapping
   (including crash-mid-run and streaming accumulation cases).
7. Comments document an API, not the code: a short docblock (at most four
   lines of prose) directly above a function, method, or class. No inline
   notes, no comment above a statement, property, or constant. Tag-only
   docblocks (`@var`, `@mago-expect`, …) are allowed anywhere because tools
   read them.
8. No nested or shorthand ternaries (`$a ? ($b ? 1 : 2) : 3`, `$a ?: $b`);
   use `if`/`match` instead.
9. No single-line control structures (`if ($x) { return; }`) and no unbraced
   bodies: the condition, each brace, and the body get their own line.

## Versioning

- Semantic versioning. The normalized model is `v1`; breaking field changes
  bump the major and require adapter plugins to bump alongside.
