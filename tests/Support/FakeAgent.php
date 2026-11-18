<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use LogicException;
use Stringable;

/**
 * A named agent for the tests, so traces carry a readable agent name.
 *
 * The invocation methods are never called by the observer and only exist to
 * satisfy the contract; where the signature is wide it is implemented with a
 * variadic, so the class does not need the broadcasting package.
 */
final class FakeAgent implements Agent
{
    public function instructions(): Stringable|string
    {
        return 'You are a test agent.';
    }

    /**
     * @param array<array-key, mixed> $attachments
     */
    public function prompt(
        $prompt,
        array $attachments = [],
        $provider = null,
        ?string $model = null,
        ?int $timeout = null
    ): AgentResponse {
        throw new LogicException('The observer never invokes an agent.');
    }

    /**
     * @param array<array-key, mixed> $attachments
     */
    public function stream(
        $prompt,
        array $attachments = [],
        $provider = null,
        ?string $model = null,
        ?int $timeout = null
    ): StreamableAgentResponse {
        throw new LogicException('The observer never invokes an agent.');
    }

    /**
     * @param array<array-key, mixed> $attachments
     */
    public function queue(
        $prompt,
        array $attachments = [],
        $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        throw new LogicException('The observer never invokes an agent.');
    }

    public function broadcast(mixed $prompt, mixed ...$channels): StreamableAgentResponse
    {
        throw new LogicException('The observer never invokes an agent.');
    }

    public function broadcastNow(mixed $prompt, mixed ...$channels): StreamableAgentResponse
    {
        throw new LogicException('The observer never invokes an agent.');
    }

    public function broadcastOnQueue(mixed $prompt, mixed ...$channels): QueuedAgentResponse
    {
        throw new LogicException('The observer never invokes an agent.');
    }
}
