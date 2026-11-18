<?php

declare(strict_types=1);

namespace CraftAiObserver\Tree;

use CraftAiObserver\Model\Completion;
use CraftAiObserver\Model\Error;
use CraftAiObserver\Model\Generation;
use CraftAiObserver\Model\Metrics;
use CraftAiObserver\Model\Model;
use CraftAiObserver\Model\ToolCall;
use CraftAiObserver\Model\Trace;
use DateTimeImmutable;

/**
 * Mutable state of one open trace; closes into an immutable Trace.
 *
 * @internal
 */
final class PendingTrace
{
    /**
     * @var list<Generation>
     */
    private array $generations = [];

    /**
     * @var array<int, DateTimeImmutable>
     */
    private array $runningSteps = [];

    /**
     * @var list<ToolCall>
     */
    private array $toolCalls = [];

    /**
     * @var array<string, array{name: string, arguments: array<string, mixed>, startedAt: DateTimeImmutable}>
     */
    private array $runningTools = [];

    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly ?string $parentTraceId,
        private readonly ?string $parentToolInvocationId,
        private readonly Model $model,
        private readonly ?string $input,
        private readonly DateTimeImmutable $startedAt
    ) {
    }

    public function startStep(int $step, DateTimeImmutable $now): void
    {
        $this->runningSteps[$step] = $now;
    }

    public function completeStep(int $step, Model $model, Completion $completion, DateTimeImmutable $now): void
    {
        $this->generations[] = new Generation(
            step: $step,
            model: new Model(
                provider: $model->provider ?? $this->model->provider,
                model: $model->model ?? $this->model->model
            ),
            completion: $completion,
            startedAt: $this->runningSteps[$step] ?? $now
        );

        unset($this->runningSteps[$step]);
    }

    public function failStep(int $step, Model $model, Error $error, ?float $latencyMs, DateTimeImmutable $now): void
    {
        $this->completeStep(
            step: $step,
            model: new Model(
                provider: $model->provider ?? $this->model->provider,
                model: $model->model ?? $this->model->model
            ),
            completion: new Completion(output: null, metrics: new Metrics(latencyMs: $latencyMs), error: $error),
            now: $now
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function startTool(string $id, string $name, array $arguments, DateTimeImmutable $now): void
    {
        $this->runningTools[$id] = ['name' => $name, 'arguments' => $arguments, 'startedAt' => $now];
    }

    public function finishTool(
        string $id,
        ?string $result,
        ?Error $error,
        ?float $latencyMs,
        DateTimeImmutable $now
    ): void {
        $running = $this->runningTools[$id] ?? null;
        unset($this->runningTools[$id]);

        $this->toolCalls[] = new ToolCall(
            id: $id,
            name: $running['name'] ?? 'unknown',
            arguments: $running['arguments'] ?? [],
            completion: new Completion(
                output: $result,
                metrics: new Metrics(
                    latencyMs: $latencyMs ?? EventReader::milliseconds($running['startedAt'] ?? null, $now)
                ),
                error: $error
            ),
            startedAt: $running['startedAt'] ?? $now
        );
    }

    public function close(Completion $completion, Model $model, DateTimeImmutable $now): Trace
    {
        return new Trace(
            id: $this->id,
            name: $this->name,
            parentTraceId: $this->parentTraceId,
            parentToolInvocationId: $this->parentToolInvocationId,
            model: new Model(
                provider: $model->provider ?? $this->model->provider,
                model: $model->model ?? $this->model->model
            ),
            input: $this->input,
            completion: new Completion(
                output: $completion->output,
                metrics: $this->totalMetrics($completion->metrics, $now),
                error: $completion->error
            ),
            startedAt: $this->startedAt,
            endedAt: $now,
            generations: $this->generations,
            toolCalls: $this->toolCalls
        );
    }

    private function totalMetrics(Metrics $fallback, DateTimeImmutable $now): Metrics
    {
        $total = $this->generations === [] ? $fallback : new Metrics();

        foreach ($this->generations as $generation) {
            $total = $total->plus($generation->completion->metrics);
        }

        return new Metrics(
            inputTokens: $total->inputTokens,
            outputTokens: $total->outputTokens,
            cacheReadTokens: $total->cacheReadTokens,
            cacheWriteTokens: $total->cacheWriteTokens,
            reasoningTokens: $total->reasoningTokens,
            latencyMs: EventReader::milliseconds($this->startedAt, $now)
        );
    }
}
