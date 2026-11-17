<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Records every log call so tests can assert on level, message, and context.
 */
class RecordingLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string, context: array<array-key, mixed>}>
     */
    public array $records = [];

    public function __construct(
        protected bool $fail = false
    ) {
    }

    /**
     * Log a message and keep it for assertions.
     *
     * @param array<array-key, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($this->fail && $level === 'debug') {
            throw new RuntimeException('The log channel is unavailable.');
        }

        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context
        ];
    }

    /**
     * @return null|array{level: string, message: string, context: array<array-key, mixed>}
     */
    public function first(string $level): ?array
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level) {
                return $record;
            }
        }

        return null;
    }

    public function hasRecorded(string $level): bool
    {
        return $this->first($level) !== null;
    }
}
