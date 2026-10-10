# Working on filament-storybook

This package brings Storybook's agent promise to Laravel and Filament: agents get structured UI context (approved patterns, their real method signatures and the rules behind them) and test feedback they can correct from. `README.md` is for people *using* the package; this file is for anyone *changing* it.

## What's where

| Path | What it is |
|---|---|
| `src/Stories.php` | Base class: one public method per story. The return type picks the canvas (component, column, action, or a whole `Table`/`Schema`). |
| `src/StoryRegistry.php` | Finds stories in `src/Stories` (shared patterns) and the app's `app/Stories`. Builds the manifest: snippets, imports, and an API section of real signatures (a tokenizer follows each call chain). |
| `src/Livewire/StoryCanvas.php` | Renders one story in its own Livewire component, like Storybook's iframe. Only registered story classes can render. |
| `src/Pages/Storybook.php` | The `/<panel>/storybook` catalogue. Not in navigation; blocked in production. |
| `src/Mcp/*` | The five Boost MCP tools: `storybook-list`, `storybook-show`, `storybook-instructions`, `storybook-test` (patterns) and `storybook-audit` (the app's real resource pages). |
| `src/Accessibility.php` + `resources/a11y/axe.mjs` | WCAG 2.1 AA checks with axe-core in headless Chromium, shared by the test and audit tools. |
| `src/StorybookPlugin.php` | Registers the page and the **house defaults**: Filament defaults corrected app-wide where they break a rule. |
| `resources/boost/guidelines/core.blade.php` | The guideline Boost puts in each app's `AGENTS.md`. Keep it short: Storybook found that long agent instructions get truncated. |
| `resources/instructions/stories.md` | How to write a story. `storybook-instructions` returns it followed by `docs/rules.md`. |
| `resources/css/house.css` | CSS for rules Filament has no setting for, published by `filament:upgrade`. |
| `docs/rules.md` | **The rulebook:** every rule with its source and date, where we differ from Carbon, and what was dropped and why. |
| `eval/` | A headless agent eval: an ordinary feature prompt in a throwaway app, scored on whether the agent used the patterns. |

## Rules policy (decided by the owner, 2026-10-10)

- **Carbon is the base design system.** GOV.UK covers form wording and error messages, and WCAG 2.2 AA always wins. A rule that follows Carbon needs no further evidence.
- **A rule beyond or against Carbon needs a current source:** W3C WAI, USWDS, NIST SP 800-63B-4, or research from about 2022 on. It goes under "Where we differ from Carbon" if it departs from Carbon.
- **No house preferences and no rules backed only by old research.** If no source sets a number (rows per page, column counts), there is no rule.
- **Mobbin** (real apps) can confirm or question a rule, never justify one. GOV.UK's style guide is UK content convention; formats follow the app's locale.
- **Carbon supplies guidance only.** Patterns use native Filament components and the app's Filament theme. Never install Carbon code, CSS, fonts or tokens. (Carbon MCP needs an IBMid, which the owner can't have. Read the public carbondesignsystem.com pages instead; `carbon-design-system/carbon-website` is an archived Markdown snapshot from 2026-09-30.)
- **The package is public.** Shared patterns stay generic: no company names, real TINs or client data. App-specific patterns live in each app's `app/Stories`.

## Changing things

- **A new rule:** check it against the live source, then add it to `docs/rules.md` with the source and date. If it's machine-checkable, add a check to `storybook-audit` (real pages) or `storybook-test` (story snippets), make the fixture in `tests/Fixtures/Gadgets` break it, and assert the warning fires.
- **A new pattern:** add a `Stories` class in `src/Stories`. Its description names its basis. Its snippets use only native Filament and paste straight into a resource. Run `storybook-test`; it must report 0 failures, 0 warnings and 0 accessibility issues.
- **A Filament default that breaks a rule:** correct it in `StorybookPlugin::applyHouseDefaults()`, which apps can turn off with `->houseDefaults(false)`. Test the default itself.
- **A tool class:** a fatal error in any one of them takes down Boost's whole MCP server, so the tests construct every tool. Don't name a method `name()`; that's laravel/mcp's.
- **Compatibility:** the tools extend laravel/mcp 1.x, and `composer.json` has `"conflict": {"laravel/mcp": ">=2.0"}`, so composer refuses an update that would break them. When laravel/mcp 2.0 ships, run the tests against it, fix the tools, and lift the limit in a release. The tools reach Boost through the `boost.mcp.tools.include` setting, which isn't in Boost's config file, so each app's `StoriesTest` asserts Boost still lists all five tools.

## Checking

- `vendor/bin/pest` runs the package tests. The accessibility checks need Node, `npm install` (dev only) and a Playwright Chromium.
- For a real-app check, copy `src`, `resources` and `docs` into a starter checkout's `vendor/josephdlmd/filament-storybook`, then call the tools through `php artisan boost:mcp` over stdio. Restore the vendor copy with `composer install` afterwards.
- Look at visual changes on the Storybook page in a browser; tests don't catch layout problems.
- After changing the agent layer (tools, guideline, instructions, rules), run `eval/run.sh <throwaway-app> <out-dir>`.

## Releasing

1. Commit to `main`, tag `vX.Y.Z` and push the tag. The package is installed from GitHub tags, not Packagist. Bump the minor version when patterns, rules or defaults change behaviour.
2. In `filament-starter`, on a branch: run `composer require josephdlmd/filament-storybook:^X.Y` (a `^0.x` constraint does not pick up the next minor version), then `php artisan boost:update`, `composer check`, and `storybook-audit`, fixing what it finds.
3. Open a PR and merge it only when the owner says so. Existing apps get the change only when someone updates them.
