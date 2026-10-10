# Filament Storybook

Storybook's promise for Laravel: agents get structured UI context (patterns, stories, the real method signatures) and test feedback, so they can self-correct and ship production Filament UI.

| Storybook | Here |
|---|---|
| CSF file: meta + named exports | A class extending `Stories`: `title()`, `description()`, one public method per story |
| Renderer + iframe | `StoryCanvas`: each story in its own Livewire component, with real state and actions |
| Docs page + sidebar | The **Storybook** panel page: live story beside its PHP snippet, grouped by title |
| `components.json` manifest | `StoryRegistry::manifest()`: imports, snippets and docblocks, read by reflection |
| `docs-list` / `docs-show` | `storybook-list` and `storybook-show`, with an API section of the real signatures of the methods each story uses |
| `test-run` + a11y addon | `storybook-test`: renders stories through the real canvas, reports failures with the story line to fix, house-rule warnings, and WCAG 2.1 AA issues from axe-core |
| `get-storybook-story-instructions` | `storybook-instructions`: the rules for writing a story and the test-and-fix loop |
| (no equivalent) | `storybook-audit`: renders the app's real resource pages signed in as a panel user, and reports render errors, rule breaks (red delete triggers, unconfirmed deletes, more than one primary action, more than two inline row actions, filters hidden in a dropdown, empty states with no next step, toggles in saved forms, untypeable dates, uploads without limits, error messages that don't say what to do, money not right-aligned, placeholders, hidden labels) and WCAG 2.1 AA issues |
| Storybook Test | A Pest test that renders every story in CI |
| AGENTS.md instructions | `resources/boost/guidelines/core.blade.php`, loaded by Boost |

## Install

### The standard

The package works with apps that follow one standard: the `filament-starter` template's stack.
- Laravel with Filament 5, and a default panel in `app/Providers/Filament`
- Laravel Boost, installed for development
- Pest
- npm (the `package.json` Laravel ships with)

Apps are brought up to this standard; the package doesn't adapt to exceptions. The installer checks it first and, if anything is missing, says what to change and touches nothing.

### Connect an app

```bash
composer config repositories.filament-storybook vcs https://github.com/josephdlmd/filament-storybook
composer require josephdlmd/filament-storybook:^0.10
php artisan storybook:install
```

The installer:
- adds the plugin to the default panel;
- registers the package with Boost and refreshes the agent guidelines;
- writes the standard `tests/Feature/StoriesTest.php`, which renders every story and fails if Boost stops serving the tools;
- publishes the house CSS and ignores it in git;
- installs the accessibility check packages.

It's safe to run again. Then:
1. Once per machine, install Chromium for the accessibility checks: `npx playwright-core install chromium`.
2. Run the tests. The house defaults change required-field errors to "Enter name" and outline delete buttons.
3. Restart your coding agent and ask it to run `storybook-audit`. On an app built before these rules, treat what it reports as a backlog to fix screen by screen.

### Accessibility checks

`storybook-test` runs axe-core on every story that renders, in headless Chromium with the panel's theme CSS, so contrast is judged on real colours. It needs Node and, in the app:

```bash
npm install --save-dev playwright-core axe-core
npx playwright-core install chromium
```

Without them the tool still runs and says the check was skipped. Set `STORYBOOK_CHROMIUM` to use another Chromium binary.

## The agent loop

1. `storybook-list`, then `storybook-show`: the agent copies an approved snippet and passes only the arguments its API section shows.
2. No pattern fits: `storybook-instructions`, then it writes a story in `app/Stories`.
3. `storybook-test` for stories and `storybook-audit` for the screens built from them: failures, house-rule warnings and accessibility issues come back with what to fix. Failures name the story line that caused them, for example `Error: Call to a member function upper() on string at app/Stories/StatusStories.php:29`. It fixes and re-runs until everything passes.

## Write a story

```php
namespace App\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class CustomerCodeStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Customer code';
    }

    public function description(): string
    {
        return 'The code on every customer form. Basis: GOV.UK text input (example in the hint, no placeholder).';
    }

    public function default(): TextInput
    {
        return TextInput::make('code')->helperText('For example, CUS-0001')->maxWidth('xs');
    }
}
```

The return type picks the canvas: a schema component (field, entry, layout), a table column, or an action. Column and entry stories take sample data from `records()`.

A whole screen is a story too. It takes and returns what the resource's `configure()` would, so its snippet pastes straight in:

```php
public function default(Table $table): Table
{
    return $table->columns([...])->filters([...])->defaultSort('name');
}
```

`Tables/List table` and `Forms/Record form` ship as the shared screen patterns. Where a very long form uses inline labels, one house CSS rule keeps them aligned; the package registers it as a Filament asset and `php artisan filament:upgrade` publishes it.

## Where the rules come from

Storybook supplies the workshop, not the rules: a design system's guidelines say what good looks like, with their evidence, and Storybook hosts them beside the code. Here that is **[docs/rules.md](docs/rules.md)**: every rule the patterns and tools apply, with its source and date, how to do it in Filament, and how it is checked, plus the rules that were dropped and why.

The base design system is **Carbon** (IBM's system for enterprise products). Form wording and error messages follow **GOV.UK**, and **WCAG 2.2 AA** always wins. A rule that follows Carbon needs no further evidence; anything beyond or against Carbon carries its own current source, and the rulebook lists where we differ from Carbon and why. House preferences and older research were removed. Real-app references from Mobbin confirm or question a rule but never justify one. `storybook-instructions` gives agents the whole rulebook.

### Adding to the standards

Ask for it in plain words, in any session:
- **A UI rule** is added when Carbon, GOV.UK form guidance, WCAG or another current source supports it. If none does, the session says so instead of adding it.
- **A business fact** (a format, a term, a domain convention) is taken from you as given.

Shared standards change only in this repo, then reach the apps through a release. Patterns for a single app go in that app's `app/Stories`.

### House defaults

Where a Filament default breaks a sourced rule, the plugin corrects it app-wide:
- A delete button among other actions is outlined (low-emphasis danger); the confirm button stays solid red (Carbon button).
- A required field's error says what to do with its own label, "Enter name" or "Select payment terms", not "The name field is required" (GOV.UK error message).

Turn this off with `StorybookPlugin::make()->houseDefaults(false)`.

## Render every story in the app's tests

`storybook:install` writes the standard `tests/Feature/StoriesTest.php` ([stubs/StoriesTest.php](stubs/StoriesTest.php)). The first test renders every story. The second fails the build if a Boost update ever stops giving agents the tools: they reach Boost through the `boost.mcp.tools.include` setting, which isn't in Boost's config file. Every app has the same file, so don't edit it there; change the stub.

## Agent eval

`eval/run.sh <app> <out>` gives a headless Claude Code an ordinary feature request ("add Suppliers") in a throwaway app that has this package and Boost, without mentioning Storybook. `eval/score.py` then reports which Storybook tools it called, whether it looked up patterns before writing Filament code, and whether the code reuses them. Run it after changing the tools, the guideline or the instructions. On v0.5.0 the agent looked up the screen and field patterns before writing code, built the resource on `Tables/List table` and `Forms/Record form` with every field pattern including the shared TIN, then ran `storybook-audit`, added a record so the edit page could be audited too, and finished clean, for $0.83 in 146s.
