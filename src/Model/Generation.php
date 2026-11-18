<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

use DateTimeImmutable;

/**
 * One provider call within a trace, i.e. one reasoning step.
 */
final readonly class Generation
{
    public function __construct(
        public int $step,
        public Model $model,
        public Completion $completion,
        public DateTimeImmutable $startedAt
    ) {
    }

    /**
     * Return a copy whose output text is capped at the given characters.
     */
    public function limit(int $characters): self
    {
        return new self(
            step: $this->step,
            model: $this->model,
            completion: $this->completion->limit($characters),
            startedAt: $this->startedAt
        );
    }

    /**
     * @return array{
     *     step: int,
     *     model: array{provider: string|null, model: string|null},
     *     output: string|null,
     *     metrics: array<string, int|float|null>,
     *     error: array{class: string, message: string, trace: string|null}|null,
     *     started_at: string,
     *     latency_ms: float|null
     * }
     */
    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'model' => $this->model->toArray(),
            'output' => $this->completion->output,
            'metrics' => $this->completion->metrics->toArray(),
            'error' => $this->completion->error?->toArray(),
            'started_at' => $this->startedAt->format(DATE_ATOM),
            'latency_ms' => $this->completion->metrics->latencyMs
        ];
    }
}
