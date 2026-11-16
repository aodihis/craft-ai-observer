# Rules — Logic (craft-ai-observer)

**Status:** Accepted
**Date:** 2026-11-16

## Span-tree semantics (map to laravel/ai events)

| laravel/ai | Observer record |
|---|---|
| `PromptingAgent` | open `Trace` (id = `invocationId`) |
| `StartingStep` | open `Generation` under the trace (`stepNumber`) |
| `StepCompleted` | close `Generation` — output, usage, latency, model from `Meta` where the provider reports it |
| `StepFailed` | close `Generation` with error + latency |
| `InvokingTool` | open `ToolCall` span → `ToolInvoked` / `ToolFailed` closes it |
| `AgentPrompted` | close the trace with final response aggregation |
| `AgentFailed` | close the trace with error |
| `AgentStreamed` | accumulate deltas into the open generation; do not close per chunk |

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
  (error), or a guard timer (e.g. 5 min) in case events are lost.
- Errors record exception class + message + trace; never the API key.
- If an observer is not registered, populating records is a no-op — zero
  overhead when observability is disabled.