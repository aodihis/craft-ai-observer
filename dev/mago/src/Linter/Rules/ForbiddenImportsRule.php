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
 * Enforces the architecture rules from docs/rules/architecture.md:
 *
 * > The library has no exporter. No Langfuse/LangSmith/OTLP code, no HTTP
 * > clients, no credentials.
 * > No dependency on `craftcms/cms`. The library must work in any Laravel app.
 *
 * Every resolved name in the file is checked, so the rule catches imports
 * (`use`), type hints, `catch` clauses, attribute classes, instantiations and
 * static references alike. The `Log` facade is deliberately *not* listed here;
 * `craft/no-facade-log` owns every reference to it.
 *
 * The rule targets the file's `Program` node and reports on the resolved-name
 * spans: mago invokes rules per matching node, and a `use` statement plus its
 * inner qualified identifier share one span, so per-node targeting would report
 * the same offset twice.
 *
 * @internal
 */
final class ForbiddenImportsRule implements Rule
{
    /**
     * Fully qualified name prefixes that must never appear in the library.
     * Trailing `\` prevents partial-name matches (e.g. `Craft\` never matches
     * `CraftAiObserver\`).
     *
     * @var non-empty-list<non-empty-string>
     */
    private const array FORBIDDEN_PREFIXES = [
        'Craft\\', // craftcms/cms — library must be host-agnostic
        'Langfuse\\', // observer SDKs belong in adapter plugins
        'LangSmith\\',
        'OpenTelemetry\\',
        'Psr\\Http\\', // no HTTP clients / export code
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
