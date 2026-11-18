# 0004 — The plugin wires the observer; the package only provides it

**Status:** Accepted
**Date:** 2026-11-17

## Context

The first implementation shipped a Laravel service provider, auto-discovered via
`composer.json` (`extra.laravel.providers`), that registered the listener for the
laravel/ai agent events and read an `enabled` flag from `config/ai-observer.php`.
Any install therefore started collecting the moment the package was required.

That puts the decision in the wrong place. Only the plugin knows whether an
observer is configured at all (credentials, endpoint, whether the site wants
telemetry), and only the plugin can add host context (which entry, user, or site
an agent run belongs to). A package that registers itself decides for the plugin,
and it cannot see any of that.

The package must also stay clear of transport entirely: no connections, no
tables, no migrations, no exporter. It supplies the abstractions and the logic
that turn events into telemetry; what happens to the telemetry is the plugin's.

## Decision

The package registers **nothing** with the host application:

- no service provider, no `extra.laravel.providers`, no `config/` file, no
  `enabled` flag — "not registered" is what disabled means;
- it exports an abstract listener
  (`CraftAiObserver\Listener\AgentEventListener`), the tree logic
  (`Tree/TraceBuilder`) and the neutral model (`Model/`). The listener is the
  extension point: the plugin subclasses it, implements `record(Trace)` and
  optionally overrides `prepare(Trace)` to trim or redact. The library never
  stores, queues, or transports anything.

The plugin owns the wiring and the transport:

```php
// craft-langfuse-integration, Plugin::init() or its service provider
Event::subscribe(LangfuseListener::class);
```

```php
final class LangfuseListener extends AgentEventListener
{
    protected function prepare(Trace $trace): ?Trace
    {
        return $trace->limit(4000);       // or redact; null drops it
    }

    protected function record(Trace $trace): void
    {
        // queue, store, send — the plugin's decision, never the library's
    }
}
```

`record()` is the only abstract method, so the package never decides what
happens to the data; `prepare()` has a default (pass-through) so adding hooks
later does not break existing subclasses.

`subscribe()` rather than `listen()`: a class-string listener is resolved per
dispatch, so each event would get a fresh `TreeBuilder` and no trace would ever
close. A subscriber is resolved once, and the open traces survive with it.

The first line is the on switch: delete it and the package does no work at all.
`ForwardTraceToLangfuse` decides which fields are sent, holds the credentials, and
writes its own lines to Craft's system log (`docs/rules/architecture.md` rule 7).

Rejected alternatives:

- **The plugin subclasses an abstract listener with overridable hooks.** Adding a
  hook is a breaking change for every subclass, protected mutable state is
  untestable in isolation, a Craft plugin already extends `craft\base\Plugin` so
  the parent slot is taken, and with one plugin it is an abstraction with a single
  caller.
- **A registry or sink interface the plugin implements.** One interface with one
  implementation, plus plumbing the event dispatcher already provides.
- **Listening inside the package, keyed on config.** The package cannot know
  whether anyone is listening or what the host context is.

## Consequences

- A plugin that forgets the `Event::listen` line records nothing, silently. That
  is accepted: the plugin is where the decision belongs, and the plugin's own
  feature test asserts the listeners are registered after boot.
- The package's runtime dependencies shrink to `laravel/ai` (the events are the
  contract), `illuminate/contracts` (the dispatcher contract) and `psr/log`;
  `illuminate/events` is a dev dependency, used by the tests.
- `docs/rules/architecture.md` rule 3 ("observer plugins are thin") still holds:
  plugins map records and transport them, they never re-implement span-tree
  logic, and ADR 0002 keeps the normalization single-sourced.
- Renaming the package is not required: it provides the observation abstractions
  and the logic, which is what "observer" describes.
