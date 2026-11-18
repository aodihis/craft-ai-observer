<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Unit\Tree;

use CraftAiObserver\Tests\Support\CreatesAiEvents;
use CraftAiObserver\Tests\Support\MutableClock;
use CraftAiObserver\Tree\TreeBuilder;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class TreeGuardTest extends TestCase
{
    use CreatesAiEvents;

    #[Test]
    public function it_closes_a_stalled_trace_as_incomplete_on_the_next_event(): void
    {
        $clock = $this->clock();
        $tree = new TreeBuilder(clock: $clock(...));
        $tree->push($this->prompting());

        $clock->tick(301);
        $traces = $tree->push(new stdClass());

        $this->assertCount(1, $traces);
        $this->assertSame('invocation-1', $traces[0]->id);
        $this->assertFalse($traces[0]->complete);
        $this->assertNull($traces[0]->completion->output);
        $this->assertNull($traces[0]->completion->error);
        $this->assertNotNull($traces[0]->endedAt);
    }

    #[Test]
    public function it_keeps_a_run_alive_while_events_keep_arriving(): void
    {
        $clock = $this->clock();
        $tree = new TreeBuilder(clock: $clock(...));
        $tree->push($this->prompting());

        $clock->tick(200);
        $this->assertSame([], $tree->push($this->startingStep()));

        $clock->tick(200);
        $this->assertSame([], $tree->push(new stdClass()));
    }

    #[Test]
    public function it_lets_the_terminal_event_close_a_trace_the_guard_would_have_taken(): void
    {
        $clock = $this->clock();
        $tree = new TreeBuilder(clock: $clock(...));
        $tree->push($this->prompting());

        $clock->tick(600);
        $traces = $tree->push($this->agentPrompted(text: 'Still answered.'));

        $this->assertCount(1, $traces);
        $this->assertTrue($traces[0]->complete);
        $this->assertSame('Still answered.', $traces[0]->completion->output);
    }

    #[Test]
    public function it_returns_the_closed_trace_before_the_expired_ones(): void
    {
        $clock = $this->clock();
        $tree = new TreeBuilder(clock: $clock(...));
        $tree->push($this->prompting('stalled-trace'));

        $clock->tick(10);
        $tree->push($this->prompting('fresh-trace'));

        $clock->tick(301);
        $traces = $tree->push($this->agentPrompted('fresh-trace'));

        $this->assertCount(2, $traces);
        $this->assertSame('fresh-trace', $traces[0]->id);
        $this->assertTrue($traces[0]->complete);
        $this->assertSame('stalled-trace', $traces[1]->id);
        $this->assertFalse($traces[1]->complete);
    }

    #[Test]
    public function the_guard_can_be_disabled(): void
    {
        $clock = $this->clock();
        $tree = new TreeBuilder(clock: $clock(...), guardSeconds: null);
        $tree->push($this->prompting());

        $clock->tick(36_000);

        $this->assertSame([], $tree->push(new stdClass()));
    }

    private function clock(): MutableClock
    {
        return new MutableClock(new DateTimeImmutable('2026-01-01 12:00:00'));
    }
}
