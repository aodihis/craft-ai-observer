# Rules — Backend (craft-ai-observer)

**Status:** Accepted
**Date:** 2026-11-16

## Environment

- PHP `^8.5` — the same version Craft 6 / DDEV run, and the only one this library targets
  (ADR 0003).
- `laravel/ai ^1.4` — the events are our contract, so the dependency is
  declared rather than assumed; `illuminate/support ^12|^13` for the service
  provider and event dispatcher, `psr/log ^3` for the injected logger.
- No dependency on `craftcms/cms`. The library must work in any Laravel
  app that uses `laravel/ai`.

## Code rules

1. PSR-4 namespace: `CraftAiObserver\...`
2. Listeners must not throw: wrap adapter dispatch in try/catch and log via
   `Psr\Log\LoggerInterface` (injected, never `Log::` facades at package
   level). That logger carries the library's own diagnostics only; writing
   records into Craft's system log is the adapter plugin's concern
   (`docs/rules/architecture.md` rule 7).
3. No database tables, no migrations, no queues (adapters may queue their
   flushes; the library hands them serializable records).
4. No package config file and no enable/disable switch: registering the
   listener is the plugin's decision, so a host that registered nothing pays
   nothing (ADR 0004).
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
