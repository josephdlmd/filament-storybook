# Working on filament-storybook

This package brings Storybook's agent promise to Laravel and Filament: agents get structured UI context (approved patterns, their real method signatures and the rules behind them) and test feedback they can correct from. `README.md` is for people *using* the package; this file is for anyone *changing* it.

## What's where

| Path | What it is |
|---|---|
| `src/Stories.php` | Base class: one public method per story. The return type picks the canvas (component, column, action, or a whole `Table`/`Schema`). |
| `src/Stories/Imports`, `src/Stories/Widgets` | Classes a pattern needs besides its stories (an importer, a chart widget). A pattern lists them in `examples()`; `storybook-show` prints their source for agents to copy. |
| `src/Concerns/HasSentenceCaseLabels.php` | A trait apps add to each resource, for the titles Filament title-cases and a plugin can't reach. |
| `src/StoryRegistry.php` | Finds stories in `src/Stories` (shared patterns) and the app's `app/Stories`. Builds the manifest: snippets, imports, and an API section of real signatures (a tokenizer follows each call chain). |
| `src/Livewire/StoryCanvas.php` | Renders one story in its own Livewire component, like Storybook's iframe. Only registered story classes can render. |
| `src/Pages/Storybook.php` | The `/<panel>/storybook` catalogue. Not in navigation; blocked in production. |
| `src/Mcp/*` | The five Boost MCP tools: `storybook-list`, `storybook-show`, `storybook-instructions`, `storybook-test` (patterns) and `storybook-audit` (the app's real resource pages, their related lists and the dashboard's widgets). |
| `src/Accessibility.php` + `resources/a11y/axe.mjs` | WCAG 2.1 AA checks with axe-core in headless Chromium, shared by the test and audit tools. |
| `src/StorybookPlugin.php` | Registers the page and the **house defaults**: Filament defaults corrected app-wide where they break a rule. |
| `resources/boost/guidelines/core.blade.php` | The guideline Boost puts in each app's `AGENTS.md`. Keep it short: Storybook found that long agent instructions get truncated. |
| `resources/instructions/stories.md` | How to write a story. `storybook-instructions` returns it followed by `docs/rules.md`. |
| `resources/css/house.css` | CSS for rules Filament has no setting for, published by `filament:upgrade`. |
| `docs/rules.md` | **The rulebook:** every rule with its source and date, where we differ from Carbon, and what was dropped and why. |
| `eval/` | A headless agent eval: an ordinary feature prompt in a throwaway app, scored on whether the agent used the patterns. |

## Apps conform to the package

The package defines one standard: the `filament-starter` stack (Filament 5 with a default panel in `app/Providers/Filament`, Laravel Boost, Pest, npm). Apps are brought up to it; the package does not add variants for apps that differ. `storybook:install` (`src/Commands/InstallCommand.php`) checks the standard, refuses with a list of what to change, and otherwise connects the app. Its edits are idempotent. The app test is one shared file, `stubs/StoriesTest.php`; change the stub, never a copy. The package itself passes Pest's `php`, `security` and `laravel` architecture presets (`tests/ArchTest.php`), because the apps run them.

## Rules policy (decided by the owner, 2026-10-10)

- **Carbon is the base design system.** GOV.UK covers form wording and error messages, and WCAG 2.2 AA always wins. A rule that follows Carbon needs no further evidence.
- **A rule beyond or against Carbon needs a current source:** W3C WAI, USWDS, NIST SP 800-63B-4, or research from about 2022 on. It goes under "Where we differ from Carbon" if it departs from Carbon.
- **No house preferences and no rules backed only by old research.** If no source sets a number (rows per page, column counts), there is no rule.
- **Mobbin** (real apps) can confirm or question a rule, never justify one. GOV.UK's style guide is UK content convention; formats follow the app's locale.
- **Carbon supplies guidance only.** Patterns use native Filament components and the app's Filament theme. Never install Carbon code, CSS, fonts or tokens. (Carbon MCP needs an IBMid, which the owner can't have. Read the public carbondesignsystem.com pages instead; `carbon-design-system/carbon-website` is an archived Markdown snapshot from 2026-09-30. Carbon's content guidelines and action-label list were taken down that day and point to IBM Style, which needs a sign-in: cite the live component and pattern pages, and the snapshot only where they are silent, marked *(archived)*.)
- **The package is public.** Shared patterns stay generic: no company names, real TINs or client data. App-specific patterns live in each app's `app/Stories`.

## The owner adds to the standards

The owner can add or change a standard at any time, by asking in plain words. Two kinds of input are accepted, and they're treated differently:
- **A UI rule** (how something should look, behave or be worded): find its basis under the policy above. If Carbon, GOV.UK form guidance, WCAG or another current source supports it, add it. If none does, tell the owner it can't be a rule under the policy, and say what the sources do say. Don't add it quietly as a "house choice".
- **A business fact** (a format, a term, a domain convention, such as "a TIN is 9 digits plus a 3-digit branch code"): the owner is the source. Record it under "Business standards" in `docs/rules.md` and in the pattern's description, as given by the owner, and keep any real data out of the public repo.

Shared standards live only here. If a session working in an app is asked to change one, it changes this repo and releases; it never edits `vendor/`.

## Changing things

- **A new rule:** check it against the live source, then add it to `docs/rules.md` with the source and date. If it's machine-checkable, add a check to `storybook-audit` (real pages) or `storybook-test` (story snippets), make the fixture in `tests/Fixtures/Gadgets` break it, and assert the warning fires.
- **A new pattern:** add a `Stories` class in `src/Stories`. Its description names its basis. Its snippets use only native Filament and paste straight into a resource. Run `storybook-test`; it must report 0 failures, 0 warnings and 0 accessibility issues.
- **A Filament default that breaks a rule:** correct it in `StorybookPlugin::applyHouseDefaults()`, which apps can turn off with `->houseDefaults(false)`. Test the default itself.
- **A tool class:** a fatal error in any one of them takes down Boost's whole MCP server, so the tests construct every tool. Don't name a method `name()`; that's laravel/mcp's.
- **Compatibility:** the tools extend laravel/mcp 1.x and plug into Boost 2.x, so `composer.json` has `"conflict": {"laravel/boost": ">=3.0", "laravel/mcp": ">=2.0"}` and composer refuses an update that could break them. It's `conflict` rather than `require` because apps install Boost only for development, while this package runs in production. When a new major version ships, run the tests against it, fix what breaks, and lift the limit in a release. The tools reach Boost through the `boost.mcp.tools.include` setting, which isn't in Boost's config file, so each app's `StoriesTest` asserts Boost still lists all five tools.

## Checking

- `vendor/bin/pest` runs the package tests. The accessibility checks need Node, `npm install` (dev only) and a Playwright Chromium.
- For a real-app check, copy `src`, `resources` and `docs` into a starter checkout's `vendor/josephdlmd/filament-storybook`, then call the tools through `php artisan boost:mcp` over stdio. Restore the vendor copy with `composer install` afterwards.
- Look at visual changes on the Storybook page in a browser; tests don't catch layout problems.
- After changing the agent layer (tools, guideline, instructions, rules), run `eval/run.sh <throwaway-app> <out-dir>`.

## Releasing

1. Commit to `main`, tag `vX.Y.Z` and push the tag. The package is installed from GitHub tags, not Packagist. Bump the minor version when patterns, rules or defaults change behaviour.
2. In `filament-starter`, on a branch: run `composer require josephdlmd/filament-storybook:^X.Y` (a `^0.x` constraint does not pick up the next minor version), then `php artisan boost:update`, `composer check`, and `storybook-audit`, fixing what it finds.
3. Open a PR and merge it only when the owner says so. Existing apps get the change only when someone updates them.
