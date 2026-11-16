# 0002 — Observer-agnostic normalized telemetry model

**Status:** Accepted
**Date:** 2026-11-16

## Context

We expect multiple observer targets over time (Langfuse now, LangSmith
later, possibly OTLP). Each target has its own schema, SDK, and quirks
(Langfuse: trace/generation/span; LangSmith: run objects; OTLP: spans).

## Decision

The library defines and emits a **neutral telemetry model** — `Trace`,
`Generation`, `Span`, `ToolCall` records with a shared field set:

- identifiers: `traceId` (= `invocationId`), `parentTraceId`,
  `parentToolInvocationId`, `stepNumber`, `toolInvocationId`
- metadata: provider, model
- I/O: input (messages/prompt), output (text / chunks), tool arguments and
  results
- metrics: input tokens, output tokens, cache read/write tokens, reasoning
  tokens, latency (ms)
- errors: exception class, message, trace

Observer plugins map this model to their platform
(`docs/product/01-why-an-observer-library.md` shows the layering). The
library stays dependency-free of any observability SDK.

## Consequences

- New observer = new thin plugin, no library changes (unless the model
  misses a field).
- Field naming must be documented and stable (`v1`).
- We pay a small mapping cost in each adapter; acceptable for the
  decoupling.