<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Mago;

use CraftAiObserver\Dev\Mago\Linter\Rules\CommentPlacementRule;
use CraftAiObserver\Dev\Mago\Linter\Rules\ForbiddenImportsRule;
use CraftAiObserver\Dev\Mago\Linter\Rules\NoFacadeLogRule;
use CraftAiObserver\Dev\Mago\Linter\Rules\NoSingleLineControlStructureRule;
use CraftAiObserver\Dev\Mago\Linter\Rules\ReadonlyValueObjectsRule;
use Mago\Sdk\Extension;

/**
 * Mago extension bundling this project's custom linter rules.
 *
 * @internal
 */
final class CraftAiObserverExtension
{
    private function __construct()
    {
    }

    public static function create(): Extension
    {
        return new Extension(
            identifier: 'craft/ai-observer-rules',
            name: 'Craft AI Observer project rules',
            version: '0.1.0',
            linterRules: [
                new CommentPlacementRule(),
                new ForbiddenImportsRule(),
                new NoFacadeLogRule(),
                new NoSingleLineControlStructureRule(),
                new ReadonlyValueObjectsRule()
            ]
        );
    }
}
