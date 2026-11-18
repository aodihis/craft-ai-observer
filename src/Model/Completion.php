<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

/**
 * What came out of a generation, a tool call, or a whole trace.
 */
final readonly class Completion
{
    public function __construct(
        public ?string $output,
        public Metrics $metrics,
        public ?Error $error = null
    ) {
    }

    /**
     * Return a copy whose output text is capped at the given characters.
     */
    public function limit(int $characters): self
    {
        return new self(
            output: self::truncate($this->output, $characters),
            metrics: $this->metrics,
            error: $this->error
        );
    }

    /**
     * @return array{
     *     output: string|null,
     *     metrics: array<string, int|float|null>,
     *     error: array{class: string, message: string, trace: string|null}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'output' => $this->output,
            'metrics' => $this->metrics->toArray(),
            'error' => $this->error?->toArray()
        ];
    }

    public static function truncate(?string $text, int $characters): ?string
    {
        if ($text === null || mb_strlen($text) <= $characters) {
            return $text;
        }

        return mb_substr(string: $text, start: 0, length: $characters) . '…';
    }
}
