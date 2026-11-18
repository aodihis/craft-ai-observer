<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Unit\Tree;

use CraftAiObserver\Tests\Support\CreatesAiEvents;
use CraftAiObserver\Tree\TreeBuilder;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TreeBuilderFailureTest extends TestCase
{
    use CreatesAiEvents;

    #[Test]
    public function it_records_a_failed_step_as_a_generation_with_an_error(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());
        $tree->push($this->startingStep());
        $this->assertNull($tree->push($this->stepFailed(error: new RuntimeException('Provider timed out.'))));

        $trace = $tree->push($this->agentFailed());

        $this->assertNotNull($trace);
        $this->assertCount(1, $trace->generations);
        $this->assertSame(RuntimeException::class, $trace->generations[0]->completion->error?->class);
        $this->assertSame(90.0, $trace->generations[0]->completion->metrics->latencyMs);
    }

    #[Test]
    public function it_records_a_failed_tool_call_without_a_result(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());
        $tree->push($this->invokingTool(toolInvocationId: 'tool-9'));
        $tree->push($this->toolFailed(toolInvocationId: 'tool-9'));

        $trace = $tree->push($this->agentPrompted());

        $this->assertNotNull($trace);
        $this->assertSame(RuntimeException::class, $trace->toolCalls[0]->completion->error?->class);
        $this->assertNull($trace->toolCalls[0]->completion->output);
    }

    #[Test]
    public function it_closes_a_failed_trace_with_the_exception(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());

        $trace = $tree->push($this->agentFailed(error: new RuntimeException('Provider timed out.')));

        $this->assertNotNull($trace);
        $error = $trace->completion->error;
        $this->assertNotNull($error);
        $this->assertSame(RuntimeException::class, $error->class);
        $this->assertSame('Provider timed out.', $error->message);
        $this->assertNull($trace->completion->output);
    }

    #[Test]
    public function it_ignores_events_for_a_trace_that_was_never_opened(): void
    {
        $tree = $this->tree();

        $this->assertNull($tree->push($this->stepCompleted()));
        $this->assertNull($tree->push($this->toolInvoked()));
        $this->assertNull($tree->push($this->agentFailed()));
    }

    private function tree(): TreeBuilder
    {
        return new TreeBuilder(clock: static fn(): DateTimeImmutable => new DateTimeImmutable('2026-01-01 12:00:00'));
    }
}
