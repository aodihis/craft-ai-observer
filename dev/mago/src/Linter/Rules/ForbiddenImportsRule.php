<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Mago\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\ResolvedName;

use function str_starts_with;
use function strtolower;

/**
 * Bans references to Craft, observer SDKs, HTTP clients and OTLP: importer
 * code and host integration belong in adapter plugins. Every resolved name is
 * checked, so imports, type hints, catch clauses and attribute classes are all
 * caught. See docs/rules/architecture.md.
 * @internal
 */
final class ForbiddenImportsRule implements Rule
{
    private const array FORBIDDEN_PREFIXES = [
        'Craft\\',
        'Langfuse\\',
        'LangSmith\\',
        'OpenTelemetry\\',
        'Psr\\Http\\',
        'GuzzleHttp\\'
    ];

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'craft/forbidden-imports',
            name: 'Forbidden observer / host imports',
            description: 'Disallows references to exporter, HTTP-client, and Craft namespaces '
            . 'that belong in adapter plugins, not in this library.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::Program]
        );
    }

    public function lint(LintContext $context): void
    {
        foreach ($context->file->getResolvedNames() as $resolved) {
            $prefix = $this->forbiddenPrefixOf($resolved);
            if ($prefix === null) {
                continue;
            }

            $context->report(Issue::new(
                "Reference to `{$resolved->name}` violates the architecture rules: "
                . 'no exporter code, no HTTP clients, no Craft dependency in this library.',
                $resolved->span,
                "Forbidden namespace `{$resolved->name}`"
            )->withHelp(
                'Adapter plugins (e.g. craft-langfuse-integration) own exporter SDKs, HTTP clients, '
                . 'and credentials. Host integration (Craft) must not leak into the library. '
                . 'See docs/rules/architecture.md.'
            ));
        }
    }

    private function forbiddenPrefixOf(ResolvedName $resolved): ?string
    {
        $lower = strtolower($resolved->name);
        foreach (self::FORBIDDEN_PREFIXES as $prefix) {
            if (str_starts_with($lower, strtolower($prefix))) {
                return $prefix;
            }
        }

        return null;
    }
}
