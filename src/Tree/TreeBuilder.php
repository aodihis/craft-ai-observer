<?php

declare(strict_types=1);

namespace CraftAiObserver\Tree;

use Closure;
use CraftAiObserver\Model\Completion;
use CraftAiObserver\Model\Metrics;
use CraftAiObserver\Model\Model;
use CraftAiObserver\Model\Trace;
use DateTimeImmutable;
use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StartingStep;
use Laravel\Ai\Events\StepCompleted;
use Laravel\Ai\Events\StepFailed;
use Laravel\Ai\Events\ToolFailed;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Folds laravel/ai events into traces (docs/rules/logic.md).
 *
 * One builder holds the open traces; feed it every event and it returns the
 * trace whenever an event closes one. Unrelated events return null.
 */
final class TreeBuilder
{
    /**
     * @var array<string, PendingTrace>
     */
    private array $open = [];

    /**
     * @param null|Closure(): DateTimeImmutable $clock
     */
    public function __construct(
        private readonly ?Closure $clock = null
    ) {
    }

    public function push(object $event): ?Trace
    {
        $now = $this->now();

        return (
            $this->closesTrace($event, $now) ?? $this->changesSteps($event, $now) ?? $this->changesTools($event, $now)
        );
    }

    private function closesTrace(object $event, DateTimeImmutable $now): ?Trace
    {
        return match (true) {
            $event instanceof AgentPrompted => $this->closeTrace(
                id: $event->invocationId,
                completion: new Completion(
                    output: $event->response->text,
                    metrics: EventReader::usage($event->response->usage)
                ),
                model: new Model(provider: $event->response->meta->provider, model: $event->response->meta->model),
                now: $now
            ),
            $event instanceof AgentFailed => $this->closeTrace(
                id: $event->invocationId,
                completion: new Completion(
                    output: null,
                    metrics: new Metrics(),
                    error: EventReader::error($event->exception)
                ),
                model: new Model(),
                now: $now
            ),
            default => null
        };
    }

    private function changesSteps(object $event, DateTimeImmutable $now): ?Trace
    {
        return match (true) {
            $event instanceof PromptingAgent => $this->openTrace(
                id: $event->invocationId,
                prompt: $event->prompt,
                now: $now
            ),
            $event instanceof StartingStep => $this->pending($event->invocationId)?->startStep(
                step: $event->stepNumber,
                now: $now
            ),
            $event instanceof StepCompleted => $this->pending($event->invocationId)?->completeStep(
                step: $event->stepNumber,
                model: new Model(
                    provider: $event->response->meta->provider ?? EventReader::provider($event->provider),
                    model: $event->response->meta->model ?? $event->model
                ),
                completion: new Completion(
                    output: $event->response->text,
                    metrics: EventReader::usage($event->response->usage, $event->time)
                ),
                now: $now
            ),
            $event instanceof StepFailed => $this->pending($event->invocationId)?->failStep(
                step: $event->stepNumber,
                model: new Model(provider: EventReader::provider($event->provider), model: $event->model),
                error: EventReader::error($event->exception),
                latencyMs: $event->time,
                now: $now
            ),
            default => null
        };
    }

    private function changesTools(object $event, DateTimeImmutable $now): ?Trace
    {
        return match (true) {
            $event instanceof InvokingTool => $this->pending($event->invocationId)?->startTool(
                id: $event->toolInvocationId,
                name: EventReader::toolName($event->tool),
                arguments: $event->arguments,
                now: $now
            ),
            $event instanceof ToolInvoked => $this->pending($event->invocationId)?->finishTool(
                id: $event->toolInvocationId,
                result: EventReader::result($event->result),
                error: null,
                latencyMs: $event->time,
                now: $now
            ),
            $event instanceof ToolFailed => $this->pending($event->invocationId)?->finishTool(
                id: $event->toolInvocationId,
                result: null,
                error: EventReader::error($event->exception),
                latencyMs: $event->time,
                now: $now
            ),
            default => null
        };
    }

    private function openTrace(string $id, AgentPrompt $prompt, DateTimeImmutable $now): ?Trace
    {
        $this->open[$id] = new PendingTrace(
            id: $id,
            name: EventReader::agentName($prompt),
            parentTraceId: $prompt->parentInvocationId,
            parentToolInvocationId: $prompt->parentToolInvocationId,
            model: new Model(provider: EventReader::provider($prompt->provider), model: $prompt->model),
            input: $prompt->prompt,
            startedAt: $now
        );

        return null;
    }

    private function closeTrace(string $id, Completion $completion, Model $model, DateTimeImmutable $now): ?Trace
    {
        $pending = $this->pending($id);

        if ($pending === null) {
            return null;
        }

        unset($this->open[$id]);

        return $pending->close(completion: $completion, model: $model, now: $now);
    }

    private function pending(string $id): ?PendingTrace
    {
        return $this->open[$id] ?? null;
    }

    private function now(): DateTimeImmutable
    {
        if ($this->clock === null) {
            return new DateTimeImmutable();
        }

        return ( $this->clock )();
    }
}
