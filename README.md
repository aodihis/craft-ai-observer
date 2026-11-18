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

This package registers nothing. It provides the listener class, the trace-building
logic, and the neutral records; a **plugin** decides whether to use them and
where the data goes (ADR 0004). Inside a plugin:

```php
// Plugin::init() or the plugin's service provider
Event::listen(AgentEventListener::EVENTS, AgentEventListener::class);
Event::listen(TraceCompleted::class, ForwardTraceToLangfuse::class);
```

The first line is the switch: without it the package does no work. `TraceCompleted`
carries the normalized `Trace`; the plugin maps it to its platform, holds the
credentials, flushes, and writes its own lines to Craft's system log.

## Development

The toolchain is [mago](https://mago.carthage.software) (Rust-based linter,
formatter, static analyzer), pinned via Composer (`carthage-software/mago`).
PHP `^8.5` is the floor, the local runtime, and the CI runner — the same version
Craft 6 runs.

```sh
composer install
composer check     # lint + format:check + analyze + verify-rules + test
composer lint      # mago lint
composer lint:fix  # mago lint --fix
composer format    # mago format
composer analyze   # mago analyze
composer verify-rules  # assert dev/mago/fixtures findings
composer test      # phpunit
```

`composer install` points `core.hooksPath` at `.githooks/`, so **every commit runs
`composer check`** and is rejected if lint, format, analysis, fixtures, or tests
fail (`git commit --no-verify` bypasses it).

CI is manual and runs the same gate on PHP 8.5:

```sh
gh workflow run CI --ref main   # or: Actions → CI → Run workflow
```
