<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Feature;

use CraftAiObserver\Listener\AgentEventListener;
use CraftAiObserver\Tests\Support\CreatesAiEvents;
use CraftAiObserver\Tests\Support\RecordingListener;
use CraftAiObserver\Tests\Support\RecordingLogger;
use CraftAiObserver\Tests\Support\TruncatingListener;
use CraftAiObserver\Tree\TreeBuilder;
use DateTimeImmutable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AgentEventListenerTest extends TestCase
{
    use CreatesAiEvents;

    #[Test]
    public function a_subclass_receives_one_finished_trace_per_run(): void
    {
        $listener = new RecordingListener($this->tree(), new RecordingLogger());
        $events = $this->events($listener);

        $events->dispatch($this->prompting());
        $events->dispatch($this->startingStep());
        $events->dispatch($this->stepCompleted());
        $events->dispatch($this->invokingTool());
        $events->dispatch($this->toolInvoked());
        $events->dispatch($this->agentPrompted());

        $this->assertCount(1, $listener->traces);
        $this->assertSame('invocation-1', $listener->traces[0]->id);
        $this->assertSame('FakeAgent', $listener->traces[0]->name);
        $this->assertCount(1, $listener->traces[0]->generations);
        $this->assertCount(1, $listener->traces[0]->toolCalls);
    }

    #[Test]
    public function the_prepare_hook_can_trim_the_trace_before_it_is_recorded(): void
    {
        $listener = new TruncatingListener($this->tree(), new RecordingLogger());
        $events = $this->events($listener);

        $events->dispatch($this->prompting());
        $events->dispatch($this->agentPrompted(text: 'Because of Rayleigh scattering.'));

        $trace = $listener->traces[0];

        $this->assertSame('Because of Rayleigh …', $trace->completion->output);
        $this->assertSame('Why is the sky blue?', $trace->input);
    }

    #[Test]
    public function nothing_is_recorded_until_the_plugin_registers_the_listener(): void
    {
        $listener = new RecordingListener($this->tree(), new RecordingLogger());
        $events = $this->dispatcher();

        $events->dispatch($this->prompting());
        $events->dispatch($this->agentPrompted());

        $this->assertCount(0, $listener->traces);
    }

    #[Test]
    public function a_failing_record_hook_never_breaks_the_run(): void
    {
        $logger = new RecordingLogger();
        $listener = new RecordingListener($this->tree(), $logger);
        $listener->explodeWhenRecording();
        $events = $this->events($listener);

        $events->dispatch($this->prompting());
        $events->dispatch($this->agentPrompted());

        $this->assertCount(0, $listener->traces);
        $this->assertTrue($logger->hasRecorded('error'));
    }

    private function events(AgentEventListener $listener): Dispatcher
    {
        $events = $this->dispatcher();
        $events->subscribe($listener);

        return $events;
    }

    private function dispatcher(): Dispatcher
    {
        $container = new Container();
        $container->instance(LoggerInterface::class, new RecordingLogger());
        $container->instance(TreeBuilder::class, $this->tree());

        $events = new Dispatcher($container);
        $container->instance(DispatcherContract::class, $events);

        return $events;
    }

    private function tree(): TreeBuilder
    {
        return new TreeBuilder(clock: static fn(): DateTimeImmutable => new DateTimeImmutable('2026-01-01 12:00:00'));
    }
}
