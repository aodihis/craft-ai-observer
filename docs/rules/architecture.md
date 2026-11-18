# Rules — Architecture (craft-ai-observer)

**Status:** Accepted
**Date:** 2026-11-16

## Layering

```
laravel/ai events (Laravel Event dispatcher)
        │
        ▼
[craft-ai-observer]  Listener(s) → TreeBuilder → NormalizedRecord
        │
        ▼
[observer plugin]  (craft-langfuse-integration, …)  Adapter → platform SDK → HTTP
```

## Hard rules

1. **The library has no exporter.** No Langfuse/LangSmith/OTLP code, no
   HTTP clients, no credentials. Anything that leaves the process belongs in
   an adapter plugin.
2. **The library is not a Craft plugin.** It is a composer package
   (`packages/craft-ai-observer`), PSR-4 autoloaded, usable in any Laravel
   host. No `<project root>/plugins` mount requirement, no CP settings UI.
3. **Observer plugins are thin.** They map `NormalizedRecord` → platform
   schema, hold credentials/config, and flush. They do not re-implement
   span-tree logic.
4. **Single source of normalization.** The event → record mapping lives
   only here, so all observers see identical data.
5. **Observability must never break the CMS.** Listeners must be
   unobservable: no exceptions propagate into the agent call, no
   performance cliffs (batch/async flush is the adapter's job, but the
   library must not block the request).
6. **Streaming is handled here.** `AgentStreamed` deltas accumulate into a
   pending generation record; the record closes on the terminal step event.
7. **Craft's system log is the plugin's job.** The library writes its own
   diagnostics through the injected `Psr\Log\LoggerInterface` and nothing
   else. Entries in Craft's system log (or any host log) that describe what
   the observer forwarded belong to the adapter plugin, next to the
   credentials and the flush it already owns — that keeps the host log free
   of observer noise when no observer is installed.
8. **The library registers nothing.** No service provider, no
   `extra.laravel.providers`, no config file, no `enabled` flag. It exports a
   listener class, the tree logic, the neutral model, and a `TraceCompleted`
   event; the plugin calls `Event::listen()` for both. Not registering is what
   "disabled" means (ADR 0004).

## Package shape

`Listener/` and `tests/` exist; `Model/`, `Tree/` and `Events/TraceCompleted`
are next. There is deliberately no `ServiceProvider` and no `config/` here: the
package registers nothing, the plugin wires it (ADR 0004).

```
craft-ai-observer/
  composer.json
  src/
    Listener/           # the listener class — registered by the plugin
    Events/             # TraceCompleted, the adapter-facing contract
    Model/              # Trace, Generation, Span, ToolCall, Metrics
    Tree/               # TreeBuilder, PendingTrace (open/close semantics)
  tests/
```
