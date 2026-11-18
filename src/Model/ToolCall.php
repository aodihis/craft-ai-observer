<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

use DateTimeImmutable;

/**
 * One tool invocation inside a trace.
 */
final readonly class ToolCall
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $arguments,
        public Completion $completion,
        public DateTimeImmutable $startedAt
    ) {
    }

    /**
     * Return a copy whose result text is capped at the given characters.
     */
    public function limit(int $characters): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            arguments: $this->arguments,
            completion: $this->completion->limit($characters),
            startedAt: $this->startedAt
        );
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     arguments: array<string, mixed>,
     *     result: string|null,
     *     error: array{class: string, message: string, trace: string|null}|null,
     *     started_at: string,
     *     latency_ms: float|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'arguments' => $this->arguments,
            'result' => $this->completion->output,
            'error' => $this->completion->error?->toArray(),
            'started_at' => $this->startedAt->format(DATE_ATOM),
            'latency_ms' => $this->completion->metrics->latencyMs
        ];
    }
}
