# craft-ai-observer

An observer-agnostic telemetry library for [laravel/ai](https://github.com/laravel/ai)
agent activity (used by Craft CMS 6).

It listens to laravel/ai's agent events and normalizes them into a neutral
trace/generation/span model that observer plugins (Langfuse, LangSmith, …)
forward to their platform.

- [Product decisions](docs/product/)
- [Engineering decisions (ADR)](docs/decision/)
- [Working rules](docs/rules/)

Changing this package? Read [AGENTS.md](AGENTS.md) first — it holds the
constraints (docs workflow, simplicity, what the tooling refuses to accept).

## Using it in Craft 6

The package registers itself: `composer.json` lists `CraftAiObserver\ServiceProvider`
under `extra.laravel.providers`, so Laravel's package discovery in a Craft 6
install boots it with no wiring. It listens to the laravel/ai agent events and
reports what it receives through the injected `Psr\Log\LoggerInterface` (the
`debug` level), which is where the normalized records will land next.

```sh
php craft vendor:publish --tag=ai-observer-config   # optional: copy config/ai-observer.php
```

Set `AI_OBSERVER_ENABLED=false` (or `'enabled' => false` in the published
config) to register no listener at all.

## Development

The toolchain is [mago](https://mago.carthage.software) (Rust-based linter,
formatter, static analyzer), pinned via Composer (`carthage-software/mago`).
PHP `8.5` is the analysis target (Craft 6 / DDEV runtime); the declared floor
is `^8.3`.

```sh
composer install
composer check     # lint + format:check + analyze + verify-rules + test
composer lint      # mago lint
composer lint:fix  # mago lint --fix
composer format    # mago format
composer analyze   # mago analyze
composer verify-rules  # exercise the custom rules against dev/mago/fixtures
composer test      # phpunit
```

### Custom linter rules

Mago is extended with project rules that encode [working rules](docs/rules/):

| Rule | Enforces |
| --- | --- |
| `craft/comment-placement` | A short comment (≤ 4 lines of prose) only directly above a function, method or class; no inline notes or comments above statements, properties or constants. Tag-only docblocks (`@var`, …) are allowed anywhere. |
| `craft/forbidden-imports` | No `Craft\`, `Langfuse\`, `LangSmith\`, `OpenTelemetry\`, `Psr\Http\`, or `GuzzleHttp\` references — exporter code and host integration belong in adapter plugins (`docs/rules/architecture.md`). |
| `craft/no-facade-log` | No `Log` facade imports or calls; inject a `Psr\Log\LoggerInterface` (`docs/rules/backend.md`). |
| `craft/no-single-line-control-structure` | No `if ($x) { return; }` / one-line loop bodies; the condition, braces and body get their own lines. |
| `craft/readonly-value-objects` | Classes under `src/Model/` must be declared `readonly` (`docs/rules/backend.md`). |

Three built-in rules are also raised from warning/note to `error` in `mago.toml`:
`no-nested-ternary`, `no-shorthand-ternary` (`$a ?: $b`), and
`block-statement` (unbraced `if`/loop bodies).

They live in `dev/mago/src/` (Mago PHP SDK) and are registered by the
`.mago/craft-worker.php` extension host configured in `mago.toml`:

```sh
mago extension list     # registered rules
mago extension validate # worker handshake
```

`dev/mago/fixtures/` holds one fixture per rule plus clean counterparts. They
are excluded from the normal run (they violate the rules on purpose) and are
only linted by `dev/mago/verify.php` through `dev/mago/fixtures/mago.toml`,
which asserts the exact set of findings (`composer verify-rules`).
