# 0001 — Listen to laravel/ai events (instead of wrapping providers)

**Status:** Accepted
**Date:** 2026-11-16

## Context

Craft CMS 6's LLM calls run through `laravel/ai` v1.x
(`Laravel\Ai\AiManager`, provider gateways speaking the OpenAI-compatible
Chat Completions protocol over `Illuminate\Http\Client`). There is no
LangChain-style layer. The only observability surface laravel/ai provides is
a **Laravel event stream**:

| Event | Payload highlights |
|---|---|
| `PromptingAgent` | `invocationId`, `AgentPrompt` (prompt, provider, model) |
| `AgentPrompted` | `invocationId`, prompt, `AgentResponse` (text, usage, meta, steps, tool calls) |
| `AgentFailed` | `invocationId`, prompt, exception |
| `AgentFailedOver` | `invocationId`, provider, model, exception (failover between providers) |
| `StartingStep` | `invocationId`, `stepNumber`, `isFinalStep`, provider, model, messages, options |
| `StepCompleted` | …, `StepResponse`, wall time in ms |
| `StepFailed` | …, exception, wall time in ms |
| `InvokingTool` / `ToolInvoked` / `ToolFailed` | `invocationId`, `toolInvocationId`, tool, arguments, result/exception, time |
| `AgentStreamed` | per-chunk streamed deltas |

Correlation is baked in: `invocationId` (UUID7), `parentInvocationId` /
`parentToolInvocationId` (sub-agents and tools chain into a parent tree),
`stepNumber` (generations within a run), `toolInvocationId` (tool spans).
`RunContext` records steps as they happen so even a crashed run can be
reported "as far as it got".

## Decision

Build observability by **subscribing to these events** with a Laravel event
listener, normalizing them into our own telemetry model.

Rationale:

- The official `langfuse/laravel` (and LangSmith) **auto-instrumentation is
  useless here**: it hooks the OpenAI/Anthropic *PHP SDKs*, but laravel/ai
  calls HTTP directly. There is no SDK call to intercept.
- Events already carry everything observers need: input messages, output
  text, input/output/cache/reasoning tokens, provider, model, millisecond
  timing, and a hierarchy of IDs.
- Zero changes to Craft, existing plugins, or how agents are invoked. We
  observe, we don't wrap.

## Alternatives considered and rejected

- **Proxy the provider base URL through Langfuse's proxy** — would capture
  payloads transparently, but breaks per-provider URLs, complicates
  streaming, and couples the *transport* to one vendor.
- **OpenTelemetry spans via `illuminate` events → OTLP** — viable later,
  but heavier and we don't need OTLP yet; the neutral event model is
  sufficient for both Langfuse and LangSmith.
- **Fork/patch laravel/ai** — maintenance burden; events were designed for
  this.

## Consequences

- We own span lifecycle management (open/close/flush) in the library.
- Race conditions / crashes mid-run must be handled by the library's
  record-keeping (mirror `RunContext` semantics).
- Streaming (`AgentStreamed`) must be accumulated before closing a
  generation.