# 01 — Why an observer library

**Type:** Product decision
**Status:** Accepted
**Date:** 2026-11-16

## The problem

Craft CMS 6 uses `laravel/ai` (Laravel's first-party AI SDK) for all LLM
work. laravel/ai exposes agent activity as a **Laravel event stream**
(`PromptingAgent`, `AgentPrompted`, `AgentFailed`, `StartingStep`,
`StepCompleted`, `StepFailed`, `InvokingTool`, `ToolInvoked`, `AgentStreamed`),
but ships **no collector, no persistence, and no observability output**.
Craft itself never listens to these events — agent telemetry is invisible
out of the box.

We want to observe agent runs: traces, generations, tools, token usage,
latency, and errors — in external platforms like Langfuse, and later
LangSmith or OTel backends.

## The approach we chose (user brief)

A **two-project architecture**:

1. **The library** (`craft-ai-observer`) — a higher-level abstraction.
   It listens to the `laravel/ai` events and normalizes them into
   observer-agnostic telemetry records. It does **not** talk to any
   observability platform.
2. **The plugins** (`craft-langfuse-integration`, later
   `craft-langsmith-integration`, …) — thin adapter plugins. They consume
   the library's normalized records and forward them to a specific
   observability platform.

We can have more than one observer plugin; they share the single library.
For now, **Langfuse is the only observer** we build against.

```
laravel/ai events ──► craft-ai-observer (listen + normalize)
                             │  normalized records
                             ▼
              craft-langfuse-integration  ──►  Langfuse
              (craft-langsmith-integration later ──►  LangSmith)
```

## Scope (library)

- Listen to laravel/ai agent events.
- Build a normalized telemetry model: trace, generation, span, tool call,
  with input/output, token usage, latency, provider/model, error info.
- Preserve the correlation already present in events
  (`invocationId`, `parentInvocationId`, `parentToolInvocationId`,
  `stepNumber`, `toolInvocationId`) as a tree.
- Provide a small public API so plugins can consume records.

## Non-goals (library)

- No network calls / no exporter code. Observers (Langfuse SDK, LangSmith
  SDK, OTLP) live in adapter plugins.
- No platform-specific model (no Langfuse schema in this package).
- No UI, no dashboard, no retention.
- Not a Craft plugin. It is a plain composer package, reusable by any
  Laravel host (Craft included).

## References

- Background investigation: see
  `docs/decision/0001-listen-to-laravel-ai-events.md` for the event surface
  and why events (not SDK hooks) are the integration point.