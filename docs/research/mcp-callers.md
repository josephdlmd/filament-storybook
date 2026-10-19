# Which callers name the MCP tools today?

Research for [#56](https://github.com/josephdlmd/filament-storybook/issues/56), part of [#54](https://github.com/josephdlmd/filament-storybook/issues/54) (Storybook's agent tools become artisan commands; decisions in [#55](https://github.com/josephdlmd/filament-storybook/issues/55)). Read on 2026-10-19 with `git grep` at each repo's `origin/main`:

| Repo | Commit |
|---|---|
| filament-storybook | `bdcab48` |
| filament-starter | `051bf15` |
| tall-flow | `2bb4e86` |
| filament-storybook-ph | `f4cdf2c` |
| accounting (read only) | `976e238` |

Searched for `storybook-(list|show|instructions|test|audit)`, `mcp__laravel-boost`, `boost.mcp`, `src/Mcp`, `Laravel\Mcp`, `FilamentStorybook\Mcp`, `laravel/mcp`, and `MCP`/`mcp`.

"Release 1" and "release 2" are #55's transition: release 1 ships the commands, the guideline names them, and MCP stays as a fallback. Release 2 removes MCP and the pins.

## filament-storybook (this repo)

### Code that serves the tools

- `src/Mcp/ListStories.php:16`, `ShowStories.php:19`, `ShowInstructions.php:14`, `TestStories.php:26`, `AuditPages.php:81`: the five tools, each extending `Laravel\Mcp\Server\Tool` and returning `Response::text`. **Needs:** move the logic each `handle()` holds into a plain class (or into the command) that both the tool and the `storybook:<name>` command call in release 1. Release 2 deletes the `Tool` wrappers.
- Text inside the tools that names other tools: `ShowStories.php:26,36` ("call storybook-list"), `TestStories.php:33,126,135` ("run storybook-test again"), `AuditPages.php:324` ("run storybook-audit again"). **Needs:** say `php artisan storybook:<name>`. While both exist, the wording must suit both callers.
- `src/FilamentStorybookServiceProvider.php:11-16,51-61`: adds the five classes to `boost.mcp.tools.include` when `Laravel\Mcp\Server\Tool` exists. **Needs:** register the commands (`$this->commands([...])`) unconditionally, because they don't need Boost. Release 2 removes this block and its imports.
- `src/Pages/Storybook.php:42`: a comment says `panel()` exists "for code that runs outside a panel request, like the MCP tools". **Needs:** change the comment to name the commands. The commands also run outside a panel request, so they need `panel()` in the same way.
- Comments that name the tools: `src/Accessibility.php:13`, `src/SettledExceptions.php:25` (check ids: `story.*` belong to storybook-test, the rest to storybook-audit), `src/Stories.php:38`, `src/StoryRegistry.php:17`, `src/StorybookPlugin.php:391`, `resources/css/theme.css:3`. **Needs:** rename only.
- `src/Commands/InstallCommand.php:17,49,67-68`: the description reads "Boost guideline and tools", and the next steps say "StoriesTest now runs storybook-test and storybook-audit" and "Restart your coding agent and ask it to run storybook-audit". **Needs:** new wording. Per #55, install also adds `Bash(php artisan storybook:*)` to the app's `.claude/settings.json`; nothing does this today. Boost is still required for the guideline (`boost:update`, line 49; requirement check, line 82).

### Agent-facing text

- `resources/boost/guidelines/core.blade.php:5,6,8`: tells agents to call `storybook-list`/`storybook-show`/`storybook-instructions` and to run `storybook-audit` "with that resource (and its `panel`…)" and `storybook-test`. **Needs:** name the commands with their real argument and option syntax (for example `php artisan storybook:audit <resource> --panel=`). Every app's `AGENTS.md`/`CLAUDE.md` takes this text on its next `boost:update`.
- `resources/instructions/stories.md:7,8,9,39,50,53,54`: names list, show (the API section, the preview link, printed examples) and test. **Needs:** command names. This text is what `storybook-instructions` itself prints.

### Tests (all call the tools as PHP objects, `new Tool)->handle(new Request(...))`)

- `tests/Pest.php:3,7,24-35`: `testStoriesWithTheme()` runs `TestStories` through `Laravel\Mcp\Request`. Most story assertions go through it.
- `tests/Feature/AgentToolsTest.php:3-10` (imports), `62,92` (expect the "run storybook-test again" wording), `118-123` (the tools join `boost.mcp.tools.include` with their five names, plus the AGENTS.md rule to "construct every tool"), `153`.
- `tests/Feature/AuditTest.php:4,9,82,361`, with about 25 `handle()` calls on `AuditPages`.
- `tests/Feature/CatalogueTest.php:4,5,17` (about 5 calls), `DocumentPatternsTest.php:4,5,11` (3 calls), `HouseDefaultsTest.php:8` (imports `Laravel\Mcp\Request`).
- `tests/Fixtures/FixturePanelProvider.php:37`, `tests/Fixtures/Gadgets/GadgetResource.php:24`, `tests/Fixtures/theme.css:1`: comments only.
- **Needs:** if the tests move to `$this->artisan('storybook:…')` with `expectsOutputToContain`/`assertExitCode`, they cover the commands and stop needing `laravel/mcp` in `require-dev`. Release 2 also drops the Boost-registration test. The smaller step is to keep calling the shared class directly and add one test per command for its arguments and exit code.

### Stub

- `stubs/StoriesTest.php:6,9,10`: imports `FilamentStorybook\Mcp`, `Laravel\Boost\Mcp\ToolRegistry` and `Laravel\Mcp\Request`.
- `stubs/StoriesTest.php:25-35`: "gives agents the storybook tools through Boost", which asserts `ToolRegistry::getAvailableTools()`.
- `stubs/StoriesTest.php:38-40,42-60`: run `TestStories`/`AuditPages` through `handle()` and match " 0 failing, 0 accessibility issues".
- **Needs:** per #55, `$this->artisan('storybook:test')->assertExitCode(0)` and `storybook:audit --panel=<id>` per panel. The Boost assertion stays through release 1 and goes in release 2. Apps get the stub only from `storybook:install` (or a copy).

### Packaging

- `composer.json:3` (description: "Boost MCP tools") and `composer.json:20` (`suggest.laravel/boost`: "…as the storybook-list … MCP tools"). **Needs:** new text. Boost stays suggested for the guideline.
- `composer.json:12`: `laravel/mcp: ^1.0` in `require-dev`, for the tests. **Needs:** removal in release 2, once no test imports `Laravel\Mcp`.
- `composer.json:24-27`: the `conflict` pins on `laravel/boost >=3.0` and `laravel/mcp >=2.0`. **Needs:** removal in release 2 (#55). They exist only because the tools extend laravel/mcp 1.x.

### Docs

- `AGENTS.md:17` (the `src/Mcp/*` row), `19, 22, 28, 60` (where checks go), `64` (a fatal error in a tool "takes down Boost's whole MCP server", and "don't name a method `name()`"), `65` (Compatibility: the conflict pins and the `StoriesTest` Boost assertion), `83` (release step: run `storybook-audit` in the starter). **Needs:** a rewrite of the "Changing things" bullets on tool classes and Compatibility in release 2. Rename the rest. The `AGENTS.md:43` hit is the filament-examples MCP and is unrelated.
- `README.md:11-14` (the Storybook-to-us table), `43`, `57` ("Restart your coding agent and ask it to run storybook-audit"), `61, 68, 72-74` (the agent loop), `177` (the StoriesTest description: "fails the build if a Boost update ever stops giving agents the tools … `boost.mcp.tools.include`"), `181` (eval: "which Storybook tools it called"). **Needs:** command names, and drop the Boost-tools test text in release 2.
- `docs/rules.md:3`, `docs/filament-coverage.md:3`, `docs/roadmap.md:11,62`: name test and audit as the checkers. **Needs:** rename only.
- `docs/carbon-coverage.md:261`, `docs/rules.md:166,216,245`, `src/Stories/DashboardStories.php:25`, `ErrorPageStories.php:17`: false positives (the word "tooltip", "MCP"-like strings, or `Laravel` in prose).

### Eval (a silent break)

- `eval/run.sh:3,13`: runs the agent with `--mcp-config .mcp.json --strict-mcp-config`, so only Boost's MCP server is available.
- `eval/run.sh:16`: `--allowedTools … "Bash(php artisan:*)" … "mcp__laravel-boost__*"`. `Bash(php artisan:*)` already allows the commands, so the eval needs no permission change.
- `eval/score.py:16`: shortens `mcp__laravel-boost__` to `boost:`.
- `eval/score.py:17`: detects audits by `"storybook-audit" in block["name"]`.
- `eval/score.py:33,35`: count Storybook use as `"storybook" in c[0]`, that is, in the tool name.
- `eval/score.py:127`: patterns shown, by `"storybook-show" in c[0]`.
- **Needs:** with commands, the tool name is `Bash` and the call is in `input.command`. As written, the score would report "storybook tools used: NONE" and "looked up patterns: false" for a perfect run. Match `php artisan storybook:<name>` in the Bash command for all four checks. The completed-audit test (`a.startswith("# ")`) holds only if the command's Markdown starts the same way. Fix this in release 1, before #55's eval runs on the commands.

## filament-starter

- `AGENTS.md:165,166,168`: the Boost-generated copy of the guideline. **Needs:** nothing by hand; `php artisan boost:update` rewrites it after the version bump.
- `AGENTS.md:47`: Boost's own "Laravel Boost is an MCP server…". Stays, because Boost's server stays.
- `tests/Feature/StoriesTest.php:6,10,25,38,42`: a copy of the stub. **Needs:** replace it with the new stub on adoption, as for every app.
- `.github/workflows/ci.yml:62`: a comment ("StoriesTest runs storybook-test and storybook-audit"). CI itself runs `php artisan test`, so it keeps working. **Needs:** rename only.
- `.claude/settings.json`: no `permissions.allow` at all, only `deny` for `.env` reads. **Needs:** add `Bash(php artisan storybook:*)` (#55).
- `.mcp.json:2-7` (`laravel-boost` → `php artisan boost:mcp`), `boost.json:7` (`"mcp": true`), `composer.json:31` (`laravel/boost ^2.10`). They stay, because Boost's MCP server stays for Boost's own tools (#54, Out of scope). The `conflict` pins come from the package, so once release 2 lifts them the starter may take Boost 3.
- `composer.json:19`: `josephdlmd/filament-storybook ^0.88`. Adopting each release needs a new `composer require …:^0.X` (AGENTS.md, Releasing step 3).
- `README.md:14`: names "Laravel Boost (MCP server…)" and Storybook. **Needs:** at most one wording check.
- `.claude/skills/infer-conventions/SKILL.md:3`: Boost's `record-rule` MCP tool. Unrelated.

## tall-flow

- `skills/verify/SKILL.md:19`: step 4 reads "Boost's `storybook-audit` MCP tool (on the `laravel-boost` server). Present when that tool is in your tool list. Call it with no arguments, then once per other panel." **Needs:** run `php artisan storybook:audit` (and `--panel=` per other panel). It is present when `php artisan list` shows `storybook:audit`, or `vendor/josephdlmd/filament-storybook` exists. Report the exit code like the other checks. This is #55's tall-flow build issue. The step also runs in worktrees, where the MCP server read the main checkout and a command reads the worktree.
- `skills/verify/SKILL.md:36`: "for the audit: issues found or none". **Needs:** the exit status, now that the audit has one.
- `docs/adr/0001-standards-via-boost-workflow-via-plugin.md:3`: "Filament Storybook's rules and MCP tools" travel through Boost. **Needs:** "tools" or "agent commands" (#55). The ADR still holds, because the commands ship in the composer package.
- `CHANGELOG.md:53`, `README.md:46`: describe `verify`'s "Storybook audit" without naming MCP. A new changelog entry is enough.
- `README.md:72`, `docs/adr/0004…:7`, `0005…:7`: about MCP servers in general, or TallStackUI's. Unrelated.
- `.scratch/tall-flow/spec.md:98,121,195`, `.scratch/tall-flow/issues/06-verify-skill.md:10,22`, `.scratch/first-trial/trial-log.md:35,71`, `.scratch/tall-flow-v0-2/backlog.md:22`: working notes and history. They need no change.
- `hooks/register.js:73` and `scripts/off-workflow.mjs:67`: guard only `migrate`/`db:wipe`, so they don't block `php artisan storybook:*`.

## filament-storybook-ph

- `resources/boost/guidelines/core.blade.php:3`: "`storybook-list` shows both", "rules, which `storybook-instructions` returns". **Needs:** command names. Ship it in the same session as core release 1, since the add-on guideline sits next to core's in every app.
- `src/FilamentStorybookPhServiceProvider.php:14`: a comment naming storybook-instructions. Rename only.
- `README.md:27`: "Run `vendor/bin/pest`; it runs `storybook-test` on every pattern here". Rename only.
- `tests/Pest.php:3,7,24-35`: the same `testStoriesWithTheme()` as core, calling `Mcp\TestStories` with `Laravel\Mcp\Request`.
- `tests/Feature/PhPatternsTest.php:4,7,361`: `ShowStories` and `ShowInstructions`, through `handle(new Request)`.
- **Needs (tests):** the add-on's tests break when core's release 2 deletes `src/Mcp`. They move to whatever core's tests move to. This is a matching add-on release (AGENTS.md, Philippine books).
- `composer.json:12`: `laravel/mcp ^1.0` in `require-dev`. **Needs:** drop it with core's release 2.
- `composer.json:9`: requires core `^0.87.1`. Needs a bump to the release that has the commands.

## accounting (read only, `origin/main` 976e238)

- `composer.json:18`: `josephdlmd/filament-storybook ^0.81.0`. That is seven minors behind the starter, so the app picks up nothing until someone updates it.
- `tests/Feature/StoriesTest.php:5,8,23-31`: an older stub copy. It has the render test and the Boost `ToolRegistry` assertion, but no `storybook-test`/`storybook-audit` tests. **Needs:** the new stub on update. The Boost assertion breaks in release 2 if the old copy is kept.
- `CLAUDE.md:201,202,204`: the Boost-generated guideline (inside `<laravel-boost-guidelines>`, lines 36-206). It is from an older version, which still says "warning". `boost:update` rewrites it.
- `CLAUDE.md:32`: **hand-written**, outside the Boost block: "The Storybook patterns (`storybook-list`, `storybook-show`) still come first". **Needs:** a manual edit to the command names.
- `.ai/rules/ux-exceptions.md:3`: hand-written: "the filament-storybook package's rules and patterns (`storybook-instructions`, `storybook-show`)". **Needs:** a manual edit to the command names.
- `.ai/rules/index.md`, `report-page.md`, `screens.md`: no tool names.
- `.claude/settings.json`: enables only `mattpocock-skills`, with no `permissions.allow`. **Needs:** `Bash(php artisan storybook:*)`, from `storybook:install` or by hand.
- `.mcp.json:2-7`, `boost.json:7`: Boost's server. It stays.
- `docs/blueprints/34,45,47,65,74,77,88,89,90,91,92-*.md` (12 files): finished blueprints that record `storybook-audit`/`storybook-test`/`storybook-show` runs. They are history, so leave them. Several record the same limit: "the Boost tools read the main checkout, not this worktree" (`77:125`, `88:17`, `89:577`, `90:479,526`, `91:633`, `92:424,457`). Because of it, audits were skipped on every worktree ticket. This is #48's "auditing the wrong checkout". A command run in the worktree fixes it.
- There is no `AGENTS.md`. `CLAUDE.md` takes its place.

## Summary of what each release must reach

- **Release 1** (commands plus the MCP fallback):
  - Core: the commands, and service-provider registration of them.
  - The shared logic out of `src/Mcp`.
  - Core and PH guidelines.
  - `resources/instructions/stories.md`.
  - The tool texts that name other tools.
  - `InstallCommand` wording, plus the `.claude/settings.json` permission.
  - The stub on `$this->artisan(...)`.
  - `eval/score.py` detection.
  - README and AGENTS.md names.
  - tall-flow `verify` step 4 and ADR 0001.
  - The starter: bump, `boost:update`, new stub, permission.
- **Release 2** (MCP removal, the open question in #54):
  - Delete `src/Mcp`'s `Tool` wrappers and the `boost.mcp.tools.include` block.
  - Delete the `conflict` pins and `laravel/mcp` in `require-dev` (core and PH).
  - Delete the stub's Boost `ToolRegistry` test and `AgentToolsTest.php:118-123`.
  - Delete AGENTS.md's tool-class and Compatibility bullets.
  - Move the PH tests off `Mcp\*` in a matching add-on release.
  - Apps still on an old stub copy (accounting) break on that test until updated.
- **By hand, outside any release:** accounting's `CLAUDE.md:32` and `.ai/rules/ux-exceptions.md:3`.
