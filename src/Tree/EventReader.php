<?php

declare(strict_types=1);

namespace CraftAiObserver\Tree;

use CraftAiObserver\Model\Error;
use CraftAiObserver\Model\Metrics;
use DateTimeImmutable;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\TextUsage;
use Stringable;
use Throwable;

use function is_scalar;
use function is_string;
use function json_encode;
use function strrpos;
use function substr;

/**
 * Reads the laravel/ai payloads into the neutral model, so the tree logic
 * never touches an SDK type itself.
 *
 * @internal
 */
final readonly class EventReader
{
    private function __construct()
    {
    }

    public static function usage(?TextUsage $usage, ?float $latencyMs = null): Metrics
    {
        if ($usage === null) {
            return new Metrics(latencyMs: $latencyMs);
        }

        return new Metrics(
            inputTokens: $usage->inputTokens,
            outputTokens: $usage->outputTokens,
            cacheReadTokens: $usage->cacheReadInputTokens,
            cacheWriteTokens: $usage->cacheWriteInputTokens,
            reasoningTokens: $usage->reasoningTokens,
            latencyMs: $latencyMs
        );
    }

    public static function error(Throwable $exception): Error
    {
        return new Error(
            class: $exception::class,
            message: $exception->getMessage(),
            trace: $exception->getTraceAsString()
        );
    }

    public static function provider(?TextProvider $provider): ?string
    {
        return $provider?->name();
    }

    public static function agentName(AgentPrompt $prompt): string
    {
        return self::baseName($prompt->agent::class);
    }

    public static function toolName(Tool $tool): string
    {
        return self::baseName($tool::class);
    }

    /**
     * Turn a tool result into something a transport can serialize.
     */
    public static function result(mixed $result): ?string
    {
        if ($result === null) {
            return null;
        }

        $encoded = is_scalar($result) || $result instanceof Stringable ? (string) $result : json_encode($result);

        return is_string($encoded) ? $encoded : null;
    }

    public static function milliseconds(?DateTimeImmutable $from, DateTimeImmutable $to): ?float
    {
        if ($from === null) {
            return null;
        }

        return (float) ( (int) $to->format('Uv') - (int) $from->format('Uv') );
    }

    private static function baseName(string $class): string
    {
        $position = strrpos(haystack: $class, needle: '\\');

        return $position === false ? $class : substr(string: $class, offset: $position + 1);
    }
}
