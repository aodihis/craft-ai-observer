<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Unit\Listener;

use CraftAiObserver\Listener\AgentEventListener;
use CraftAiObserver\Tests\Support\RecordingLogger;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\StartingStep;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Prompts\AgentPrompt;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AgentEventListenerTest extends TestCase
{
    #[Test]
    public function it_records_the_correlation_ids_of_an_event(): void
    {
        $logger = new RecordingLogger();

        new AgentEventListener($logger)->handle($this->startingStep());

        $record = $logger->first('debug') ?? $this->fail('Nothing was recorded.');

        $this->assertSame('invocation-1', $record['context']['trace_id']);
        $this->assertSame(2, $record['context']['step']);
        $this->assertSame('gpt-6-sol', $record['context']['model']);
    }

    #[Test]
    public function it_records_the_exception_of_a_failed_event(): void
    {
        $logger = new RecordingLogger();
        $event = new AgentFailed(
            'invocation-1',
            $this->createStub(AgentPrompt::class),
            new RuntimeException('Provider timed out.')
        );

        new AgentEventListener($logger)->handle($event);

        $record = $logger->first('debug') ?? $this->fail('Nothing was recorded.');

        $this->assertSame(RuntimeException::class, $record['context']['error']);
        $this->assertSame('Provider timed out.', $record['context']['error_message']);
    }

    #[Test]
    public function it_ignores_events_it_does_not_know(): void
    {
        $logger = new RecordingLogger();

        new AgentEventListener($logger)->handle(new \stdClass());

        $record = $logger->first('debug') ?? $this->fail('Nothing was recorded.');

        $this->assertSame(['event' => \stdClass::class], $record['context']);
    }

    #[Test]
    public function it_never_throws_when_the_log_channel_fails(): void
    {
        $logger = new RecordingLogger(fail: true);

        new AgentEventListener($logger)->handle($this->startingStep());

        $this->assertTrue($logger->hasRecorded('error'));
    }

    #[Test]
    public function it_listens_to_the_subclass_events_that_laravel_dispatches_by_exact_name(): void
    {
        $this->assertContains(StreamingAgent::class, AgentEventListener::EVENTS);
        $this->assertContains(AgentStreamed::class, AgentEventListener::EVENTS);
    }

    #[Test]
    public function every_listened_event_exists_in_the_installed_laravel_ai(): void
    {
        foreach (AgentEventListener::EVENTS as $event) {
            $this->assertTrue(class_exists($event), "Missing event class: {$event}");
        }
    }

    private function startingStep(): StartingStep
    {
        return new StartingStep(
            'invocation-1',
            2,
            $this->createStub(Agent::class),
            $this->createStub(TextProvider::class),
            'gpt-6-sol',
            false,
            [],
            null
        );
    }
}
