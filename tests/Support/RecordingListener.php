<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use CraftAiObserver\Listener\AgentEventListener;
use CraftAiObserver\Model\Trace;

/**
 * Stands in for the adapter plugin: records (or keeps) what it is handed.
 */
class RecordingListener extends AgentEventListener
{
    /**
     * @var list<Trace>
     */
    public array $traces = [];

    private bool $explode = false;

    public function explodeWhenRecording(): void
    {
        $this->explode = true;
    }

    protected function record(Trace $trace): void
    {
        if ($this->explode) {
            throw new \RuntimeException('The queue is unavailable.');
        }

        $this->traces[] = $trace;
    }
}
