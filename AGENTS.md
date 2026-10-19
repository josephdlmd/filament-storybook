# Working on filament-storybook

This package brings Storybook's agent promise to Laravel and Filament: agents get structured UI context (approved patterns, their real method signatures and the rules behind them) and test feedback they can correct from. `README.md` is for people *using* the package; this file is for anyone *changing* it.

## What's where

| Path | What it is |
|---|---|
| `src/Stories.php` | Base class: one public method per story. The return type picks the canvas (component, column, action, or a whole `Table`/`Schema`). |
| `src/Documents`, `src/Contacts`, `src/Reports` | Classes a rule or business standard fixes (`DocumentTotals`, `DocumentNumber`, `DocumentStatus`, `PaymentStatus`, `PrintedDocument`, `PhoneNumber`, the abstract `ReportExporter`): apps call or extend them, never copy them; `storybook-show` prints their signatures. Keep their public methods stable, since apps depend on them by version. |
| `src/Stories/Imports`, `src/Stories/Widgets`, `src/Stories/Documents` | Example classes a pattern needs besides its stories (an importer, a chart widget, a model). A pattern lists them in `examples()`; `storybook-show` prints their source for agents to copy. The example models there (`Customer`, `SalesInvoice`) stand in for the app's own; `Customer` keeps its sample rows in an in-memory SQLite database, so a story can use a real relationship (`Stories::model()`). |
| `resources/views/documents` | The shared print layout for every document (`print` wraps `document` as a full page for the PDF; the Storybook page previews `document`). |
| `src/Concerns/HasSentenceCaseLabels.php` | A trait apps add to each resource, for the titles Filament title-cases and a plugin can't reach. |
| `src/StoryRegistry.php` | Finds stories in `src/Stories` (shared patterns) and the app's `app/Stories`. Builds the manifest: snippets, imports, and an API section of real signatures (a tokenizer follows each call chain). |
| `src/Livewire/StoryCanvas.php` | Renders one story in its own Livewire component, like Storybook's iframe. Only registered story classes can render. |
| `src/Pages/Storybook.php` | The `/<panel>/storybook` catalogue. Not in navigation; blocked in production. |
| `src/Mcp/*` | The five Boost MCP tools: `storybook-list`, `storybook-show`, `storybook-instructions`, `storybook-test` (patterns) and `storybook-audit` (the app's real resource pages, their related lists, the panel's own pages, the dashboard's widgets and the menu). |
| `src/Accessibility.php` + `resources/a11y/axe.mjs` | WCAG 2.2 AA checks with axe-core in headless Chromium, shared by the test and audit tools. |
| `src/CustomComponents.php` | What counts as an app's custom component, which `storybook-test` and `storybook-audit` fail: custom components are package-only. |
| `src/StorybookPlugin.php` | Registers the page, the locked look's colours and compact sizes, and the **house defaults**: Filament defaults corrected app-wide where they break a rule. |
| `resources/boost/guidelines/core.blade.php` | The guideline Boost puts in each app's `AGENTS.md`. Keep it short: Storybook found that long agent instructions get truncated. |
| `resources/instructions/stories.md` | How to write a story: choosing the component, fitting the other patterns, checking the result. `storybook-instructions` returns it followed by `docs/filament-coverage.md` and `docs/rules.md`. |
| `resources/js/house.js` | Script for rules Filament can't meet with CSS: copyable values the keyboard can use. Loaded on every panel page. |
| `resources/css/house.css`, `resources/css/compact.css` | CSS for rules Filament has no setting for, and Carbon's compact sizes, published by `filament:upgrade`. |
| `resources/css/theme.css` | The panel theme every app imports: Filament's theme with its Compact theme. |
| `docs/rules.md` | **The rulebook:** every rule with its source and date, where we differ from Carbon, and what was dropped and why. |
| `docs/carbon-coverage.md` | Every Carbon page checked against the rules and the compact CSS: one row per page with its updated date, and each gap as a numbered finding with its status. Re-check a page when its date changes. |
| `docs/filament-coverage.md` | Every Filament component and the guidance that covers it (a pattern, a rule, general rules, or a gap), so a new component or a gap is visible. Agents look components up in it (through `storybook-instructions`), and a test fails when Filament ships a class it doesn't list. |
| `docs/roadmap.md` | What is covered, what comes next, and the business facts to ask the owner first. |
| `eval/` | A headless agent eval: an ordinary feature prompt in a throwaway app, scored on whether the agent used the patterns. |

## Apps conform to the package

The package defines one standard: the `filament-starter` stack (Filament 5 with a default panel in `app/Providers/Filament`, Laravel Boost, Pest, npm). Apps are brought up to it; the package does not add variants for apps that differ. `storybook:install` (`src/Commands/InstallCommand.php`) checks the standard, refuses with a list of what to change, and otherwise connects the app. Its edits are idempotent. The app test is one shared file, `stubs/StoriesTest.php`; change the stub, never a copy. The package itself passes Pest's `php`, `security` and `laravel` architecture presets (`tests/ArchTest.php`), because the apps run them.

## Rules policy (decided by the owner, 2026-10-10)

- **Carbon is the base design system.** GOV.UK covers form wording and error messages, and WCAG 2.2 AA always wins. A rule that follows Carbon needs no further evidence.
- **A rule beyond or against Carbon needs a current source:** W3C WAI, USWDS, NIST SP 800-63B-4, or research from about 2022 on. It goes under "Where we differ from Carbon" if it departs from Carbon.
- **No house preferences and no rules backed only by old research.** If no source sets a number (rows per page, column counts), there is no rule.
- **IBM Accessibility's content design pages** (ibm.com/able, public) replace the writing guidance Carbon took down (owner, 2026-10-11).
- **FilaCheck Pro is Filament's code best practice, not UI guidance** (owner, 2026-10-13). Follow it on how Filament is used: deprecated methods, defaults, enums, validation, tests. Its UI, UX and layout suggestions (add search, add filters, group a long form) are applied only where Carbon or another source above agrees, and Carbon wins where they differ.
- **FilamentExamples** (filamentexamples.com projects, through the `filament-examples` MCP's `search-examples`) is working Filament code, under the same terms as FilaCheck (owner, 2026-10-13): look there for how to build something before writing a pattern, but its screens never justify a UI rule, and the patterns win wherever they cover the screen. It's a paid service, so shared patterns never depend on it or copy its code.
- **Mobbin** (real apps) can confirm or question a rule, never justify one. GOV.UK's style guide is UK content convention; formats follow the app's locale.
- **Carbon supplies guidance only.** Patterns use native Filament components and the locked look (`docs/rules.md`, The look; owner, 2026-10-19), which `StorybookPlugin` and the package theme set for every app. Never install Carbon code, CSS, fonts or tokens. Compact is the only density: `resources/css/compact.css` sets Filament's sizes, spacing and text weights to Carbon's specified values where Filament is bigger (owner, 2026-10-11). (Carbon MCP needs an IBMid, which the owner can't have. Read the public carbondesignsystem.com pages instead; `carbon-design-system/carbon-website` is an archived Markdown snapshot from 2026-09-30. Carbon's content guidelines and action-label list were taken down that day and point to IBM Style, which needs a sign-in: cite the live component and pattern pages, and the snapshot only where they are silent, marked *(archived)*.)
- **Native Filament first** (owner, 2026-10-18), so patterns stay fast and keep working across Filament upgrades. Try these in order (owner, 2026-10-19): (1) native components (fields, columns, entries, actions, and layout such as Section, Grid, Flex, Callout and Empty state); (2) schema prime components (Text, Icon, Image, UnorderedList) composed with that layout, with no custom class or view; (3) only for a gap in `docs/filament-coverage.md`, a custom component. It extends a Filament base class. Its Blade view uses a Filament Blade component wherever one exists (button, icon button, link, badge, section, callout, empty state, dropdown, modal, tabs, input wrapper, input, select, checkbox, fieldset, avatar, breadcrumbs, pagination, loading indicator), and plain HTML only for structure, styled with Filament's theme variables: no colours, fonts or sizes of its own, and no JavaScript beyond Alpine. It ships in the package with a story and passes `storybook-test`, keyboard use included. Apps never write one; a gap goes to the owner as an extension proposal.
- **The package is public.** Shared patterns stay generic: no company names, real TINs or client data. App-specific patterns live in each app's `app/Stories`.
- **Philippine books are an add-on** (owner, 2026-10-19): VAT, withholding, TINs and BIR forms live in `josephdlmd/filament-storybook-ph` (`~/Projects/filament-storybook-ph`), public too, layered on core: its classes extend core's (`DocumentTotals`, `PrintedDocument`), its stories show only what they add and name the core pattern, it adds sample columns through `SampleDatabase::extend()` and its rules through `StoryRegistry::addRules()`. Core stays generic. A core release that breaks the add-on ships with a matching add-on release in the same session.

## The owner adds to the standards

The owner can add or change a standard at any time, by asking in plain words. Two kinds of input are accepted, and they're treated differently:
- **A UI rule** (how something should look, behave or be worded): find its basis under the policy above. If Carbon, GOV.UK form guidance, WCAG or another current source supports it, add it. If none does, tell the owner it can't be a rule under the policy, and say what the sources do say. Don't add it quietly as a "house choice".
- **A business fact** (a format, a term, a domain convention, such as "a TIN is 9 digits plus a 3-digit branch code"): the owner is the source. Record it under "Business standards" in `docs/rules.md` and in the pattern's description, as given by the owner, and keep any real data out of the public repo.

Shared standards live only here. If a session working in an app is asked to change one, it changes this repo and releases; it never edits `vendor/`.

## Changing things

- **A new rule:** check it against the live source, then add it to `docs/rules.md` with the source and date. If it's machine-checkable, add a check to `storybook-audit` (real pages) or `storybook-test` (story snippets), give it a stable check id (`area.what`; story checks start with `story.`), make the fixture in `tests/Fixtures/Gadgets` break it, and assert it fails by its id.
- **A new pattern:** add a `Stories` class in `src/Stories`. Its description names its basis. Its snippets use only native Filament and paste straight into a resource. A `Table` or `Schema` story is a screen: it declares its states (`#[States]`, or `#[Record]`, `#[Section]`; see `resources/instructions/stories.md`). Run `storybook-test`; it must report 0 failing and 0 accessibility issues.
- **Strict mode** (owner, 2026-10-19): every check fails; there is no warning tier and no setting to turn it off. Only the owner settles an exception, which the app lists in `config/storybook-exceptions.php` (check id, target, field, reason, who approved it and when; see `src/SettledExceptions.php`). Accessibility issues are never settled. A misfiring check is fixed in the check, not settled.
- **A Filament default that breaks a rule:** correct it in `StorybookPlugin::applyHouseDefaults()`, which every panel gets. Test the default itself.
- **A tool class:** a fatal error in any one of them takes down Boost's whole MCP server, so the tests construct every tool. Don't name a method `name()`; that's laravel/mcp's.
- **Compatibility:** the tools extend laravel/mcp 1.x and plug into Boost 2.x, so `composer.json` has `"conflict": {"laravel/boost": ">=3.0", "laravel/mcp": ">=2.0"}` and composer refuses an update that could break them. It's `conflict` rather than `require` because apps install Boost only for development, while this package runs in production. When a new major version ships, run the tests against it, fix what breaks, and lift the limit in a release. The tools reach Boost through the `boost.mcp.tools.include` setting, which isn't in Boost's config file, so each app's `StoriesTest` asserts Boost still lists all five tools.

## Checking

- `vendor/bin/pest` runs the package tests. The accessibility checks need Node, `npm install` (dev only) and a Playwright Chromium. Tests live in `tests/Feature`, one file per area (catalogue, house defaults, agent tools, audit, install, field and document patterns); shared setup is in `tests/Pest.php`. GitHub Actions runs them on every push.
- For a real-app check, copy `src`, `resources` and `docs` into a starter checkout's `vendor/josephdlmd/filament-storybook`, then call the tools through `php artisan boost:mcp` over stdio. Restore the vendor copy with `composer install` afterwards.
- `filacheck src` (FilaCheck Pro, installed globally with Composer, since it's a paid package) should find only UI suggestions the rules overrule, such as `table-without-searchable-columns` on a list that stays short (see the rules policy). The fixtures break rules on purpose, so never run `--fix` on `tests`. FilaCheck also rewrites some files without `--fix` (its `deprecated-url-parameters` rule turns `set('activeTab', …)` into `set('tab', …)`, which breaks Livewire tests, since `tab` is only the URL name): run it on a copy, or on a committed tree and check `git diff` after.
- Look at every visual change on the Storybook page in Chrome, at desktop and phone (320 px) width, and show the owner the screenshots in the session summary (owner, 2026-10-18); tests don't catch layout problems.
- If a session changed the agent layer (tools, guideline, instructions, rules), run `eval/run.sh <throwaway-app> <out-dir>` once, before the session's release (owner, 2026-10-18).

## Releasing

Release once per session, at its end, not after each change (owner, 2026-10-18).

Only the owner merges to `main`, creates version tags and merges starter PRs (owner, 2026-10-19). `main` and `v*` tags are protected; a session may push any other branch, open PRs and draft releases, and work issues.

1. Work on one branch per session and open one PR to `main`. The owner reviews and merges it once the Tests workflow is green.
2. End the session with a draft GitHub release carrying the proposed `vX.Y.Z` and its notes (`gh release create vX.Y.Z --draft --target main --notes …`). The owner merges the PR, then publishes the release, which tags the merged `main`. The package is installed from GitHub tags, not Packagist. Bump the minor version when patterns, rules or defaults change behaviour.
3. Once the tag exists, in `filament-starter` on a branch: run `composer require josephdlmd/filament-storybook:^X.Y` (a `^0.x` constraint does not pick up the next minor version), then `php artisan boost:update`, `composer check`, and `storybook-audit`, fixing what it finds. Open a PR; it waits for the owner, since adopting a release approves it. Existing apps get the change only when someone updates them.
