# AGENTS.md

Constraints for anyone (agent or human) changing this package. Read it before touching code, and
treat it as binding rather than as a tour: the "why" and the model live in `docs/`, the links below
tell you where.

`craft-ai-observer` is a Composer library (`craftcms/craft-ai-observer`) that turns `laravel/ai`
events into a neutral trace model. It ships inside Craft CMS 6 but must work in any Laravel app.

---

## 1. How to work with docs

`docs/` is the specification. Code follows the docs; the docs never follow the code.

**Read before you write.** For any change, read the documents that own it:

- `docs/product/` — why this package exists and who it is for (integrators writing observer plugins).
- `docs/decision/` — ADRs. They are numbered, dated, and **Accepted**. Each records a choice we are
  not free to silently revisit.
- `docs/rules/` — the constraints: `architecture.md` (layering, no exporter, no Craft dependency),
  `backend.md` (code rules), `logic.md` (span-tree semantics). These are the contract.

**Three homes only. Never invent a fourth.**

| Kind of statement | Goes to | Shape |
| --- | --- | --- |
| Why the package exists, who uses it | `docs/product/NN-topic.md` | Short, second person, usage-oriented |
| A decision we made, and what it rules out | `docs/decision/NNNN-topic.md` | Context → Decision → Consequences |
| A constraint code must satisfy | `docs/rules/*.md` | Numbered imperative rules |

Class-by-class internals belong in the code, not in docs. If a fact is only useful to someone
editing a file, write it as a docblock on that file and stop there.

**Never edit an Accepted ADR.** Supersede it: new number, `**Status:** Accepted`, and one line in
the old file's status (`**Status:** Superseded by 0007`). Decisions get dates (`YYYY-MM-DD`), not
"recently".

**Docs change in the same commit as the code.** Not the next one. A removal is a documentation
change too: deleting a method or a knob means deleting or rewriting the paragraph that promised it.
Rewrite in place — appending a contradicting paragraph is how docs rot.

**Docs describe what shipped.** No "planned", "coming soon", "TODO: document later". If it is not in
`src/`, it is not in `docs/`.

**Rules are executable.** Every rule in `docs/rules/` is enforced by a mago rule in
`dev/mago/src/Linter/Rules/` (or a built-in raised in `mago.toml`). Changing one means changing all
three in the same commit: the rule text, the mago rule, and a fixture in `dev/mago/fixtures/` that
proves it fires (and a clean fixture proving it does not over-fire). `composer verify-rules` fails
if fixtures drift.

---

## 2. Keep it stupid and simple

The default answer is less code.

- **Smallest thing that works.** No abstraction until the second caller exists: no interface with
  one implementation, no factory for one product class, no event for one listener, no config knob
  nobody asked for. A strategy pattern with one strategy is a rename.
- **Use what already exists.** PHP stdlib, Laravel/Illuminate events, PSR interfaces
  (`Psr\Log\LoggerInterface`), Symfony components. Before writing something, ask whether a popular
  library already does it — if it does, use it rather than hand-rolling a narrower version.
  Never write your own event emitter, retry loop, serializer, or HTTP layer.
- **A new dependency is a decision, not a convenience.** It must be small, maintained, and must not
  pull in a framework, an exporter SDK, or `craftcms/cms` (see `docs/rules/architecture.md`).
  Justify it in the commit message; record it in an ADR if it shapes the design.
- **No clever code.** Early returns over nesting; names that say what they mean; explicit beats
  magic (`__call`, dynamic properties, stringly-typed config). If a reader needs the author to
  explain it, rewrite it.
- **Readable over DRY.** Two similar five-line blocks are better than one parameterised block with
  three booleans.
- **Delete more than you add.** Dead code, unused parameters, and compatibility shims for code that
  has never shipped go. Renaming unreleased code is editing, not a breaking change.
- **Fail loudly at the edges, never inward.** Listeners must not throw into the host application —
  that is a hard rule, not a style preference (`docs/rules/architecture.md`, `docs/rules/logic.md`).

---

## 3. Hard lines

Not up for reinterpretation in a PR. Details and rationale live in `docs/rules/`.

1. No exporter: no Langfuse/LangSmith/OTLP SDKs, no HTTP clients, no credentials, no flush logic.
2. No `craftcms/cms` dependency. The library must run in a plain Laravel app.
3. Observability never breaks the CMS: no exceptions escape a listener, no blocking I/O in the
   request path.
4. Records are immutable value objects (`readonly`) — `src/Model/` classes are enforced by
   `craft/readonly-value-objects`.
5. Streaming deltas accumulate into the open generation; they are not separate records.
6. `declare(strict_types=1)`, PSR-12, fully typed, PHP `^8.3` syntax only (local is 8.5; CI runs
   8.3).

Breaking one of these is an ADR plus a rule change, never a one-off commit.

---

## 4. The toolchain is the gate

```sh
composer check   # mago lint + format:check + analyze + verify-rules — must be green
composer format  # also normalises CRLF (Windows editors) to LF per .gitattributes
```

- `composer check` green before you hand anything over. If it fails, the failure is yours to fix.
- **Do not silence findings.** No baselines, no blanket ignores. A suppression is a last resort and
  must carry a one-line reason in the same docblock (`@mago-expect analysis:mixed-assignment` above
  JSON narrowing is the accepted example in `dev/mago/verify.php`).
- **Do not work around a project rule.** The rules are the point: comments go above functions or
  nowhere, no nested/shorthand ternaries, no single-line control structures, no `Log` facade, no
  comments narrating a line you just changed. Moving a comment to dodge the linter, or splitting one
  unreadable expression into two unreadable ones, is worse than deleting it.
- **Tests.** PHPUnit (`composer test`), run by `composer check`. Unit tests for logic, feature tests
  for framework wiring (`Illuminate\Events\Dispatcher`, container resolution) — test behaviour through
  the public API, no bespoke harness, no implementation-detail assertions.
- **CI** (`.github/workflows/ci.yml`) runs `composer check` on PHP 8.3 and 8.5. It has no other
  hidden requirements; if it passes locally it passes there.

---

## 5. Commits

Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `test:`), one logical change per commit.
The message states what changed and why in a sentence or two — the rules and fixtures that moved
with it are worth a line, since that is where reviewers look.

## 6. Done means

- `composer check` green.
- `docs/` updated in the same commit — or deliberately not, and you said why.
- Rule docs, mago rule, and fixtures moved together if a constraint changed.
- No new dependency without justification; no new abstraction without a second caller.
- Nothing in the diff that the diff does not need.
