<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

/**
 * The exception behind a failed generation, tool call, or trace.
 */
final readonly class Error
{
    public function __construct(
        public string $class,
        public string $message,
        public ?string $trace = null
    ) {
    }

    /**
     * @return array{class: string, message: string, trace: string|null}
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'message' => $this->message,
            'trace' => $this->trace
        ];
    }
}
