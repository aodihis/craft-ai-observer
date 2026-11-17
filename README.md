# craft-ai-observer

An observer-agnostic telemetry library for [laravel/ai](https://github.com/laravel/ai)
agent activity (used by Craft CMS 6).

It listens to laravel/ai's agent events and normalizes them into a neutral
trace/generation/span model that observer plugins (Langfuse, LangSmith, …)
forward to their platform.

- [Product decisions](docs/product/)
- [Engineering decisions (ADR)](docs/decision/)
- [Working rules](docs/rules/)

## Development

The toolchain is [mago](https://mago.carthage.software) (Rust-based linter,
formatter, static analyzer), pinned via Composer (`carthage-software/mago`).
PHP `8.5` is the analysis target (Craft 6 / DDEV runtime); the declared floor
is `^8.3`.

```sh
composer install
composer check     # lint + format:check + analyze + verify-rules
composer lint      # mago lint
composer lint:fix  # mago lint --fix
composer format    # mago format
composer analyze   # mago analyze
composer verify-rules  # exercise the custom rules against dev/mago/fixtures
```

### Custom linter rules

Mago is extended with project rules that encode [working rules](docs/rules/):

| Rule | Enforces |
| --- | --- |
| `craft/forbidden-imports` | No `Craft\`, `Langfuse\`, `LangSmith\`, `OpenTelemetry\`, `Psr\Http\`, or `GuzzleHttp\` references — exporter code and host integration belong in adapter plugins (`docs/rules/architecture.md`). |
| `craft/no-facade-log` | No `Log` facade imports or calls; inject a `Psr\Log\LoggerInterface` (`docs/rules/backend.md`). |
| `craft/readonly-value-objects` | Classes under `src/Model/` must be declared `readonly` (`docs/rules/backend.md`). |

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
