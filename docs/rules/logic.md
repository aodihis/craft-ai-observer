# Rules — Logic (craft-ai-observer)

**Status:** Accepted
**Date:** 2026-11-16

## Span-tree semantics (map to laravel/ai events)

| laravel/ai | Observer record |
|---|---|
| `PromptingAgent`, `StreamingAgent` | open `Trace` (id = `invocationId`, input = prompt) |
| `StartingStep` | start `Generation` for the trace (`stepNumber`) |
| `StepCompleted` | close `Generation` — output, usage, latency, model from `Meta` where the provider reports it |
| `StepFailed` | close `Generation` with error + latency |
| `InvokingTool` | open `ToolCall` → `ToolInvoked` / `ToolFailed` closes it |
| `AgentPrompted`, `AgentStreamed` | close the trace with the final text, usage, and error (if any) |
| `AgentFailed` | close the trace with error |

`AgentStreamed` is a terminal event: laravel/ai v1 combines the streaming deltas
itself and dispatches it once with the combined response, so the trace closes on
it and no per-chunk record exists. `StreamingAgent` (the start) extends
`PromptingAgent` and `AgentStreamed` extends `AgentPrompted`; the listener
returns the trace when an event closes one, and `null` otherwise.

The listener hands the finished trace to its subclass: `prepare()` may trim or
redact it (`Trace::limit()` is the built-in trimmer, return `null` to drop it),
then `record()` does whatever the plugin wants — queue it, store it, send it.
The library never stores or transports anything.

The adapter-facing shape is the serialized record (`Trace::toArray()`): ids,
`complete` (false only for guard-expired traces),
`model` (`provider`/`model`), `input`, `output`, `metrics` (tokens +
`latency_ms`), `error`, `started_at`/`ended_at`, and the `generations` and
`tool_calls` lists. `input` is the trace-level prompt; per-step messages are
deliberately not part of v1. Keys are snake_case and stable — adapters map this,
they never re-read laravel/ai.

## Hierarchy rules

1. `invocationId` is the trace root.
2. `AgentPrompt.parentInvocationId` / `parentToolInvocationId`: an agent run
   launched from inside a tool (sub-agent) nests under that tool span and
   its parent trace → the record for the sub-run carries `parentTraceId` /
   `parentToolInvocationId` so adapters can nest.
3. `stepNumber` orders generations within a trace (multi-step reasoning
   loops make several generations per trace).
4. `toolInvocationId` identifies each tool span, even across failover
   attempts.

## Usage & latency

- `TextUsage`: `inputTokens`, `outputTokens`, `cacheReadInputTokens`,
  `cacheWriteInputTokens`, `reasoningTokens`.
- Latency: from `StepCompleted`/`StepFailed` `time` (ms) — wall time of the
  provider call; the library may add its own envelope timing for
  tool-to-flush.

## Failure semantics

- Mirror laravel/ai's `RunContext` behavior: track steps/tool results as
  they happen so a crashed run still yields a partial, accurate trace.
- A trace always closes: either via `AgentPrompted` (success), `AgentFailed`
  (error), or the guard. The guard is **idle-based**: a trace that no event has
  touched for `guardSeconds` (default 300) is closed by the next event with
  `complete: false`, no output, and no error — so a long run that keeps emitting
  steps survives, while lost terminal events stop leaking. Pass
  `guardSeconds: null` to `TreeBuilder` to disable it. The guard runs after the
  event itself is routed, so a terminal event that arrives late still closes its
  own trace normally.
- Errors record exception class + message + trace; never the API key.
- If an observer is not registered, populating records is a no-op — zero
  overhead when observability is disabled.
