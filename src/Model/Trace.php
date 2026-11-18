<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

use DateTimeImmutable;

/**
 * One agent run: the trace root an adapter receives, with its generations and
 * tool calls. Traces nest through the parent ids, never through the arrays.
 */
final readonly class Trace
{
    /**
     * @param list<Generation> $generations
     * @param list<ToolCall> $toolCalls
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentTraceId,
        public ?string $parentToolInvocationId,
        public Model $model,
        public ?string $input,
        public Completion $completion,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public array $generations = [],
        public array $toolCalls = [],
        public bool $complete = true
    ) {
    }

    /**
     * Return a copy whose input, output, and tool results are capped at the
     * given characters. Arguments and metrics are left untouched.
     */
    public function limit(int $characters): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            parentTraceId: $this->parentTraceId,
            parentToolInvocationId: $this->parentToolInvocationId,
            model: $this->model,
            input: Completion::truncate($this->input, $characters),
            completion: $this->completion->limit($characters),
            startedAt: $this->startedAt,
            endedAt: $this->endedAt,
            generations: array_map(static fn(Generation $generation): Generation => $generation->limit(
                $characters
            ), $this->generations),
            toolCalls: array_map(static fn(ToolCall $toolCall): ToolCall => $toolCall->limit(
                $characters
            ), $this->toolCalls),
            complete: $this->complete
        );
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     parent_trace_id: string|null,
     *     parent_tool_invocation_id: string|null,
     *     model: array{provider: string|null, model: string|null},
     *     input: string|null,
     *     output: string|null,
     *     metrics: array<string, int|float|null>,
     *     error: array{class: string, message: string, trace: string|null}|null,
     *     started_at: string,
     *     ended_at: string|null,
     *     generations: list<array<string, mixed>>,
     *     tool_calls: list<array<string, mixed>>,
     *     complete: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_trace_id' => $this->parentTraceId,
            'parent_tool_invocation_id' => $this->parentToolInvocationId,
            'model' => $this->model->toArray(),
            'input' => $this->input,
            'output' => $this->completion->output,
            'metrics' => $this->completion->metrics->toArray(),
            'error' => $this->completion->error?->toArray(),
            'started_at' => $this->startedAt->format(DATE_ATOM),
            'ended_at' => $this->endedAt?->format(DATE_ATOM),
            'generations' => array_map(
                static fn(Generation $generation): array => $generation->toArray(),
                $this->generations
            ),
            'tool_calls' => array_map(static fn(ToolCall $toolCall): array => $toolCall->toArray(), $this->toolCalls),
            'complete' => $this->complete
        ];
    }
}
