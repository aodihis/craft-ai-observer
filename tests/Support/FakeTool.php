<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * A named tool for the tree tests, so traces carry a readable tool name.
 */
final class FakeTool implements Tool
{
    public function description(): string
    {
        return 'Fetches the weather.';
    }

    public function handle(Request $request): string
    {
        return 'sunny';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
