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
composer check     # lint + format:check + analyze
composer lint      # mago lint
composer lint:fix  # mago lint --fix
composer format    # mago format
composer analyze   # mago analyze
```