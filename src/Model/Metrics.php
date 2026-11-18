<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

/**
 * Token counts and latency of one generation, or of a whole trace.
 */
final readonly class Metrics
{
    public function __construct(
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $cacheReadTokens = null,
        public ?int $cacheWriteTokens = null,
        public ?int $reasoningTokens = null,
        public ?float $latencyMs = null
    ) {
    }

    /**
     * Add another set of metrics, summing whatever both sides reported.
     */
    public function plus(self $other): self
    {
        return new self(
            inputTokens: self::sumInt($this->inputTokens, $other->inputTokens),
            outputTokens: self::sumInt($this->outputTokens, $other->outputTokens),
            cacheReadTokens: self::sumInt($this->cacheReadTokens, $other->cacheReadTokens),
            cacheWriteTokens: self::sumInt($this->cacheWriteTokens, $other->cacheWriteTokens),
            reasoningTokens: self::sumInt($this->reasoningTokens, $other->reasoningTokens),
            latencyMs: self::sumFloat($this->latencyMs, $other->latencyMs)
        );
    }

    /**
     * @return array{
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     cache_read_tokens: int|null,
     *     cache_write_tokens: int|null,
     *     reasoning_tokens: int|null,
     *     latency_ms: float|null
     * }
     */
    public function toArray(): array
    {
        return [
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'cache_read_tokens' => $this->cacheReadTokens,
            'cache_write_tokens' => $this->cacheWriteTokens,
            'reasoning_tokens' => $this->reasoningTokens,
            'latency_ms' => $this->latencyMs
        ];
    }

    private static function sumInt(?int $left, ?int $right): ?int
    {
        return $left === null && $right === null ? null : ( $left ?? 0 ) + ( $right ?? 0 );
    }

    private static function sumFloat(?float $left, ?float $right): ?float
    {
        return $left === null && $right === null ? null : ( $left ?? 0.0 ) + ( $right ?? 0.0 );
    }
}
