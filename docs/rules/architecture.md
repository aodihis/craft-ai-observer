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

## Package shape (when we get there)

```
craft-ai-observer/
  composer.json
  src/
    Listener/           # subscribes to laravel/ai events
    Model/              # Trace, Generation, Span, ToolCall, Metrics
    Tree/               # TreeBuilder, PendingTrace (open/close semantics)
    ServiceProvider.php # Laravel service provider registering listeners
  config/
    ai-observer.php     # enable/disable knobs (defaults on)
  tests/
```