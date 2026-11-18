<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\TextGenerationLoop;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use LogicException;

/**
 * A named text provider for the tests. The call methods are never reached by
 * the observer; they exist to satisfy the vendor contract.
 *
 * @mago-expect lint:too-many-methods — mirrors the twelve-method TextProvider contract
 */
final class FakeProvider implements TextProvider
{
    public function name(): string
    {
        return 'openrouter';
    }

    public function driver(): string
    {
        return 'openrouter';
    }

    /**
     * @return array<string, mixed>
     */
    public function providerCredentials(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function additionalConfiguration(): array
    {
        return [];
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): static
    {
        return $this;
    }

    public function prompt(AgentPrompt $prompt): AgentResponse
    {
        throw new LogicException('The observer never prompts a provider.');
    }

    public function stream(AgentPrompt $prompt): StreamableAgentResponse
    {
        throw new LogicException('The observer never streams from a provider.');
    }

    public function useTextGateway(StepTextGateway $gateway): self
    {
        return $this;
    }

    public function textGenerationLoop(): TextGenerationLoop
    {
        throw new LogicException('The observer never runs a generation loop.');
    }

    public function defaultTextModel(): string
    {
        return 'gpt-6-sol';
    }

    public function cheapestTextModel(): string
    {
        return 'gpt-6-sol';
    }

    public function smartestTextModel(): string
    {
        return 'gpt-6-sol';
    }
}
