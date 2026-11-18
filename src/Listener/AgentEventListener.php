<?php

declare(strict_types=1);

namespace CraftAiObserver\Listener;

use CraftAiObserver\Model\Trace;
use CraftAiObserver\Tree\TreeBuilder;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Events\{
    AgentFailed,
    AgentPrompted,
    AgentStreamed,
    InvokingTool,
    PromptingAgent,
    StartingStep,
    StepCompleted,
    StepFailed,
    StreamingAgent,
    ToolFailed,
    ToolInvoked
};
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The extension point: a plugin subclasses this, registers it with
 * `Event::subscribe()`, and overrides the hooks to decide what happens to the
 * structured trace. Storage, queueing, and transport live in the subclass.
 * See docs/rules/logic.md.
 */
abstract class AgentEventListener
{
    /**
     * @var non-empty-list<class-string>
     */
    public const array EVENTS = [
        PromptingAgent::class,
        StreamingAgent::class,
        StartingStep::class,
        StepCompleted::class,
        StepFailed::class,
        InvokingTool::class,
        ToolInvoked::class,
        ToolFailed::class,
        AgentPrompted::class,
        AgentStreamed::class,
        AgentFailed::class
    ];

    public function __construct(
        private readonly TreeBuilder $tree,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Register this listener for every agent event, in the host dispatcher.
     */
    final public function subscribe(Dispatcher $events): void
    {
        foreach (self::EVENTS as $event) {
            $events->listen($event, [$this, 'handle']);
        }
    }

    /**
     * Fold one event into its trace and hand the finished trace to the hooks.
     */
    final public function handle(object $event): void
    {
        try {
            $trace = $this->tree->push($event);

            if ($trace === null) {
                return;
            }

            $prepared = $this->prepare($trace);

            if ($prepared !== null) {
                $this->record($prepared);
            }
        } catch (Throwable $exception) {
            $this->logger->error('AI observer could not record {event}.', [
                'event' => $event::class,
                'exception' => $exception
            ]);
        }
    }

    /**
     * Shape the trace before it leaves the library: trim it (`Trace::limit()`),
     * redact it, or return `null` to drop it. The default passes it unchanged.
     */
    protected function prepare(Trace $trace): ?Trace
    {
        return $trace;
    }

    /**
     * Take the finished trace: queue it, store it, send it, or ignore it.
     */
    abstract protected function record(Trace $trace): void;
}
