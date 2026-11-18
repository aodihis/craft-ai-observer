<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Unit\Tree;

use CraftAiObserver\Model\Trace;
use CraftAiObserver\Tests\Support\CreatesAiEvents;
use CraftAiObserver\Tree\TreeBuilder;
use DateTimeImmutable;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Responses\Data\TextUsage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class TreeBuilderTest extends TestCase
{
    use CreatesAiEvents;

    #[Test]
    public function it_returns_nothing_until_an_event_closes_a_trace(): void
    {
        $tree = $this->tree();

        $this->assertSame([], $tree->push($this->prompting()));
        $this->assertSame([], $tree->push($this->startingStep()));
        $this->assertSame([], $tree->push(new stdClass()));
    }

    #[Test]
    public function it_closes_a_trace_on_the_agent_prompted_event(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());

        $trace = $this->closed($tree, $this->agentPrompted(text: 'Because of Rayleigh scattering.'));

        $this->assertInstanceOf(Trace::class, $trace);
        $this->assertSame('invocation-1', $trace->id);
        $this->assertSame('FakeAgent', $trace->name);
        $this->assertSame('Why is the sky blue?', $trace->input);
        $this->assertSame('Because of Rayleigh scattering.', $trace->completion->output);
        $this->assertSame('openrouter', $trace->model->provider);
        $this->assertSame('gpt-6-sol', $trace->model->model);
        $this->assertNotNull($trace->endedAt);
    }

    #[Test]
    public function it_keeps_every_step_as_a_generation_in_order(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());
        $tree->push($this->startingStep(step: 1));
        $tree->push($this->stepCompleted(step: 1, text: 'First thought.', usage: new TextUsage(10, 20)));
        $tree->push($this->startingStep(step: 2));
        $tree->push($this->stepCompleted(step: 2, text: 'Second thought.', usage: new TextUsage(30, 40)));

        $trace = $this->closed($tree, $this->agentPrompted());

        $this->assertNotNull($trace);
        $this->assertCount(2, $trace->generations);
        $this->assertSame(1, $trace->generations[0]->step);
        $this->assertSame('First thought.', $trace->generations[0]->completion->output);
        $this->assertSame(2, $trace->generations[1]->step);
        $this->assertSame(30, $trace->generations[1]->completion->metrics->inputTokens);
        $this->assertSame(60, $trace->completion->metrics->outputTokens);
    }

    #[Test]
    public function it_records_a_tool_call_with_arguments_result_and_latency(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());
        $tree->push($this->invokingTool(arguments: ['city' => 'Utrecht']));
        $tree->push($this->toolInvoked(result: 'sunny', time: 15.0));

        $trace = $this->closed($tree, $this->agentPrompted());

        $this->assertNotNull($trace);
        $this->assertCount(1, $trace->toolCalls);
        $this->assertSame('FakeTool', $trace->toolCalls[0]->name);
        $this->assertSame(['city' => 'Utrecht'], $trace->toolCalls[0]->arguments);
        $this->assertSame('sunny', $trace->toolCalls[0]->completion->output);
        $this->assertSame(15.0, $trace->toolCalls[0]->completion->metrics->latencyMs);
    }

    #[Test]
    public function it_closes_a_streamed_run_once_at_the_agent_streamed_event(): void
    {
        $tree = $this->tree();

        $this->assertSame([], $tree->push(new StreamingAgent('invocation-1', $this->agentPrompt())));

        $trace = $this->closed($tree, $this->agentPrompted(text: 'Streamed answer.'));

        $this->assertNotNull($trace);
        $this->assertSame('Streamed answer.', $trace->completion->output);
        $this->assertSame([], $tree->push($this->agentPrompted()));
    }

    #[Test]
    public function it_nests_a_sub_agent_trace_through_the_parent_ids(): void
    {
        $tree = $this->tree();
        $prompt = $this->agentPrompt(parentInvocationId: 'parent-trace', parentToolInvocationId: 'tool-7');

        $tree->push($this->prompting('invocation-2', $prompt));
        $trace = $this->closed($tree, $this->agentPrompted('invocation-2'));

        $this->assertNotNull($trace);
        $this->assertSame('parent-trace', $trace->parentTraceId);
        $this->assertSame('tool-7', $trace->parentToolInvocationId);
    }

    #[Test]
    public function the_trace_serializes_to_the_documented_shape(): void
    {
        $tree = $this->tree();
        $tree->push($this->prompting());
        $tree->push($this->startingStep());
        $tree->push($this->stepCompleted());
        $tree->push($this->invokingTool());
        $tree->push($this->toolInvoked());

        $data = $this->closed($tree, $this->agentPrompted())->toArray();

        $this->assertNotNull($data);
        $this->assertSame('invocation-1', $data['id']);
        $this->assertSame('openrouter', $data['model']['provider']);
        $this->assertSame('2026-01-01T12:00:00+00:00', $data['started_at']);
        $this->assertCount(1, $data['generations']);
        $this->assertCount(1, $data['tool_calls']);
        $this->assertArrayHasKey('latency_ms', $data['metrics']);
    }

    private function closed(TreeBuilder $tree, object $event): Trace
    {
        $traces = $tree->push($event);
        $this->assertCount(1, $traces);

        return $traces[0];
    }

    private function tree(): TreeBuilder
    {
        return new TreeBuilder(clock: static fn(): DateTimeImmutable => new DateTimeImmutable('2026-01-01 12:00:00'));
    }
}
