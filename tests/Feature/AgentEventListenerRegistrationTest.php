<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Feature;

use CraftAiObserver\Listener\AgentEventListener;
use CraftAiObserver\Tests\Support\RecordingLogger;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Events\StartingStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AgentEventListenerRegistrationTest extends TestCase
{
    #[Test]
    public function the_host_registers_the_listener_and_the_dispatcher_calls_it(): void
    {
        $logger = new RecordingLogger();
        $container = new Container();
        $container->instance(LoggerInterface::class, $logger);

        $events = new Dispatcher($container);
        $events->listen(AgentEventListener::EVENTS, AgentEventListener::class);

        $events->dispatch(
            new StartingStep(
                'invocation-1',
                1,
                $this->createStub(Agent::class),
                $this->createStub(TextProvider::class),
                'gpt-6-sol',
                false,
                [],
                null
            )
        );

        $record = $logger->first('debug') ?? $this->fail('The listener was not called.');

        $this->assertSame(StartingStep::class, $record['context']['event']);
        $this->assertSame('invocation-1', $record['context']['trace_id']);
    }

    #[Test]
    public function nothing_is_recorded_until_the_host_registers_the_listener(): void
    {
        $logger = new RecordingLogger();
        $container = new Container();
        $container->instance(LoggerInterface::class, $logger);
        $events = new Dispatcher($container);

        $events->dispatch(
            new StartingStep(
                'invocation-1',
                1,
                $this->createStub(Agent::class),
                $this->createStub(TextProvider::class),
                'gpt-6-sol',
                false,
                [],
                null
            )
        );

        $this->assertFalse($logger->hasRecorded('debug'));
    }
}
