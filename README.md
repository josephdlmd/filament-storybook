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
| (no equivalent) | `storybook-audit`: renders the app's real resource pages signed in as a panel user, and reports render errors, page-level house-rule breaks (money not right-aligned, placeholders, hidden labels) and WCAG 2.1 AA issues |
| Storybook Test | A Pest test that renders every story in CI |
| AGENTS.md instructions | `resources/boost/guidelines/core.blade.php`, loaded by Boost |

## Install

Not on Packagist; add the repository first:

```bash
composer config repositories.filament-storybook vcs https://github.com/josephdlmd/filament-storybook
composer require josephdlmd/filament-storybook:^0.7
```

Register the plugin in the panel provider. The page is left out of the navigation (open `/<panel>/storybook`, or the link `storybook-show` gives) and is blocked in production:

```php
->plugin(\Josephdlmd\FilamentStorybook\StorybookPlugin::make())
```

Add `"josephdlmd/filament-storybook"` to `packages` in `boost.json`, then run `php artisan boost:update` so agents get the guideline. The four MCP tools join Boost's server automatically.

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

Storybook supplies the workshop, not the rules: a design system's guidelines say what good looks like, with their evidence, and Storybook hosts them beside the code. Here every pattern description, instruction and warning names its basis, and a rule is only kept when current, maintained guidance supports it:

- **Standards:** WCAG 2.2 and the W3C WAI tutorials (forms tutorial, 2024).
- **Maintained design systems with user research:** GOV.UK Design System, U.S. Web Design System (v3.14, 2026) and Carbon.
- **Research no older than about 2022** where a design system is silent, such as NN/g data tables (2022).

Rules that rested only on preference (25 rows per page, at most 8 columns) or on old research were removed. Real-app references from Mobbin confirm or question a rule but never justify one: common is not the same as good, and Stripe, for one, still puts examples in placeholders. The accessibility checks are WCAG 2.1 AA, run by axe-core, the engine behind Storybook's own accessibility addon.

## Render every story in the app's tests

```php
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Livewire\Livewire;

it('renders every stories pattern', function () {
    foreach (app(StoryRegistry::class)->all() as $stories) {
        foreach (array_keys($stories->stories()) as $story) {
            Livewire::test(StoryCanvas::class, ['stories' => $stories::class, 'story' => $story])->assertOk();
        }
    }
});
```

## Agent eval

`eval/run.sh <app> <out>` gives a headless Claude Code an ordinary feature request ("add Suppliers") in a throwaway app that has this package and Boost, without mentioning Storybook. `eval/score.py` then reports which Storybook tools it called, whether it looked up patterns before writing Filament code, and whether the code reuses them. Run it after changing the tools, the guideline or the instructions. On v0.5.0 the agent looked up the screen and field patterns before writing code, built the resource on `Tables/List table` and `Forms/Record form` with every field pattern including the shared TIN, then ran `storybook-audit`, added a record so the edit page could be audited too, and finished clean, for $0.83 in 146s.
