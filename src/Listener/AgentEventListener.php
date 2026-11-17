<?php

declare(strict_types=1);

namespace CraftAiObserver\Listener;

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
 * Receives the laravel/ai agent events that make up an agent run and hands
 * them to the observer. Registered by the service provider; it must never
 * throw into the host application. See docs/rules/logic.md.
 */
class AgentEventListener
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
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle a laravel/ai agent event.
     */
    public function handle(object $event): void
    {
        try {
            $this->logger->debug('AI observer received {event}.', ['event' => $event::class] + $this->context($event));
        } catch (Throwable $exception) {
            $this->logger->error('AI observer could not record {event}.', [
                'event' => $event::class,
                'exception' => $exception
            ]);
        }
    }

    /**
     * The correlation ids of an event, keyed by their neutral name.
     *
     * @return array<non-empty-string, string|int|float>
     */
    protected function context(object $event): array
    {
        return match (true) {
            $event instanceof PromptingAgent => ['trace_id' => $event->invocationId],
            $event instanceof StartingStep => [
                'trace_id' => $event->invocationId,
                'step' => $event->stepNumber,
                'model' => $event->model
            ],
            $event instanceof StepCompleted, $event instanceof StepFailed => [
                'trace_id' => $event->invocationId,
                'step' => $event->stepNumber,
                'model' => $event->model,
                'milliseconds' => $event->time
            ],
            $event instanceof InvokingTool => [
                'trace_id' => $event->invocationId,
                'tool_invocation_id' => $event->toolInvocationId
            ],
            $event instanceof ToolInvoked, $event instanceof ToolFailed => [
                'trace_id' => $event->invocationId,
                'tool_invocation_id' => $event->toolInvocationId,
                'milliseconds' => $event->time
            ],
            $event instanceof AgentPrompted => ['trace_id' => $event->invocationId],
            $event instanceof AgentFailed => [
                'trace_id' => $event->invocationId,
                'error' => $event->exception::class,
                'error_message' => $event->exception->getMessage()
            ],
            default => []
        };
    }
}
