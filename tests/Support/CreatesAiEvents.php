<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StartingStep;
use Laravel\Ai\Events\StepCompleted;
use Laravel\Ai\Events\StepFailed;
use Laravel\Ai\Events\ToolFailed;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Throwable;

/**
 * Builds the laravel/ai events the tree builder consumes.
 */
trait CreatesAiEvents
{
    protected function agentPrompt(
        string $prompt = 'Why is the sky blue?',
        ?string $parentInvocationId = null,
        ?string $parentToolInvocationId = null
    ): AgentPrompt {
        return new AgentPrompt(
            agent: new FakeAgent(),
            prompt: $prompt,
            attachments: [],
            provider: new FakeProvider(),
            model: 'gpt-6-sol',
            parentInvocationId: $parentInvocationId,
            parentToolInvocationId: $parentToolInvocationId
        );
    }

    protected function prompting(string $invocationId = 'invocation-1', ?AgentPrompt $prompt = null): PromptingAgent
    {
        return new PromptingAgent($invocationId, $prompt ?? $this->agentPrompt());
    }

    protected function startingStep(string $invocationId = 'invocation-1', int $step = 1): StartingStep
    {
        return new StartingStep(
            $invocationId,
            $step,
            new FakeAgent(),
            new FakeProvider(),
            'gpt-6-sol',
            false,
            [],
            null
        );
    }

    protected function stepCompleted(
        string $invocationId = 'invocation-1',
        int $step = 1,
        string $text = 'Partly blue.',
        ?TextUsage $usage = null,
        float $time = 120.0
    ): StepCompleted {
        return new StepCompleted(
            $invocationId,
            $step,
            new FakeAgent(),
            new FakeProvider(),
            'gpt-6-sol',
            true,
            new StepResponse(
                $text,
                [],
                FinishReason::Stop,
                $usage ?? new TextUsage(10, 20),
                new Meta('openrouter', 'gpt-6-sol')
            ),
            $time
        );
    }

    protected function stepFailed(
        string $invocationId = 'invocation-1',
        int $step = 1,
        ?Throwable $error = null,
        float $time = 90.0
    ): StepFailed {
        return new StepFailed(
            $invocationId,
            $step,
            new FakeAgent(),
            new FakeProvider(),
            'gpt-6-sol',
            true,
            $error ?? new \RuntimeException('Provider timed out.'),
            $time
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function invokingTool(
        string $invocationId = 'invocation-1',
        string $toolInvocationId = 'tool-1',
        array $arguments = ['city' => 'Utrecht']
    ): InvokingTool {
        return new InvokingTool($invocationId, $toolInvocationId, new FakeAgent(), new FakeTool(), $arguments);
    }

    protected function toolInvoked(
        string $invocationId = 'invocation-1',
        string $toolInvocationId = 'tool-1',
        mixed $result = 'sunny',
        float $time = 15.0
    ): ToolInvoked {
        return new ToolInvoked(
            $invocationId,
            $toolInvocationId,
            new FakeAgent(),
            new FakeTool(),
            ['city' => 'Utrecht'],
            $result,
            $time
        );
    }

    protected function toolFailed(
        string $invocationId = 'invocation-1',
        string $toolInvocationId = 'tool-1',
        ?Throwable $error = null,
        float $time = 15.0
    ): ToolFailed {
        return new ToolFailed(
            $invocationId,
            $toolInvocationId,
            new FakeAgent(),
            new FakeTool(),
            ['city' => 'Utrecht'],
            $error ?? new \RuntimeException('Weather service down.'),
            $time
        );
    }

    protected function agentPrompted(
        string $invocationId = 'invocation-1',
        string $text = 'Because of Rayleigh scattering.',
        ?TextUsage $usage = null
    ): AgentPrompted {
        return new AgentPrompted(
            $invocationId,
            $this->agentPrompt(),
            new AgentResponse(
                $invocationId,
                $text,
                $usage ?? new TextUsage(10, 20),
                new Meta('openrouter', 'gpt-6-sol')
            )
        );
    }

    protected function agentFailed(string $invocationId = 'invocation-1', ?Throwable $error = null): AgentFailed
    {
        return new AgentFailed(
            $invocationId,
            $this->agentPrompt(),
            $error ?? new \RuntimeException('Provider timed out.')
        );
    }
}
