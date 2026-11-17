<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Mago\Linter\Rules;

use Mago\Sdk\Exception\InvalidArgumentException;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

use function str_contains;
use function str_replace;

/**
 * Enforces docs/rules/backend.md:
 *
 * > Records are value objects (readonly), PSR-12, strict_types, typed.
 *
 * Classes declared under `src/Model/` (Trace, Generation, Span, ToolCall,
 * Metrics, …) must be `readonly`.
 *
 * @internal
 */
final class ReadonlyValueObjectsRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'craft/readonly-value-objects',
            name: 'Model classes must be readonly',
            description: 'Requires `readonly` on value-object classes declared under src/Model/.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Class_]
        );
    }

    public function lint(LintContext $context): void
    {
        if (!$this->isModelFile($context->file->path)) {
            return;
        }

        if ($this->hasReadonlyModifier($context)) {
            return;
        }

        $context->report(Issue::new(
            'Model classes are value objects and must be declared `readonly`.',
            $context->node->span,
            annotationMessage: 'Missing `readonly` modifier'
        )->withHelp(
            'The normalized model (Trace, Generation, Span, ToolCall, Metrics, …) is a set of immutable '
            . 'value objects. Declare the class `readonly`. See docs/rules/backend.md.'
        ));
    }

    private function isModelFile(string $path): bool
    {
        $normalized = str_replace(search: '\\', replace: '/', subject: $path);

        return str_contains($normalized, '/src/Model/');
    }

    private function hasReadonlyModifier(LintContext $context): bool
    {
        foreach ($context->getChildren() as $child) {
            if ($child->kind !== NodeKind::Modifier) {
                continue;
            }

            if ($this->modifierText($context, $child) === 'readonly') {
                return true;
            }
        }

        return false;
    }

    private function modifierText(LintContext $context, Node $modifier): string
    {
        try {
            return $context->file->getText($modifier);
        } catch (InvalidArgumentException) {
            // Snapshot without source text: cannot inspect the modifier, so
            // treat the class as conformant rather than reporting noise.
            return '';
        }
    }
}
