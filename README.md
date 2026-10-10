# Filament Storybook

Storybook's promise for Laravel: agents get structured UI context (patterns, stories, the real method signatures) and test feedback, so they can self-correct and ship production Filament UI.

| Storybook | Here |
|---|---|
| CSF file: meta + named exports | A class extending `Stories`: `title()`, `description()`, one public method per story |
| Renderer + iframe | `StoryCanvas`: each story in its own Livewire component, with real state and actions |
| Docs page + sidebar | The **Storybook** panel page: live story beside its PHP snippet, grouped by title |
| `components.json` manifest | `StoryRegistry::manifest()`: imports, snippets and docblocks, read by reflection |
| `docs-list` / `docs-show` | `storybook-list` and `storybook-show`, with an API section of the real signatures of the methods each story uses |
| `test-run` | `storybook-test`: renders stories through the real canvas and reports failures with the story line to fix |
| `get-storybook-story-instructions` | `storybook-instructions`: the rules for writing a story and the test-and-fix loop |
| Storybook Test | A Pest test that renders every story in CI |
| AGENTS.md instructions | `resources/boost/guidelines/core.blade.php`, loaded by Boost |

## Install

Not on Packagist; add the repository first:

```bash
composer config repositories.filament-storybook vcs https://github.com/josephdlmd/filament-storybook
composer require josephdlmd/filament-storybook:^0.2
```

Register the plugin in the panel provider. The page is left out of the navigation (open `/<panel>/storybook`, or the link `storybook-show` gives) and is blocked in production:

```php
->plugin(\Josephdlmd\FilamentStorybook\StorybookPlugin::make())
```

Add `"josephdlmd/filament-storybook"` to `packages` in `boost.json`, then run `php artisan boost:update` so agents get the guideline. The four MCP tools join Boost's server automatically.

## The agent loop

1. `storybook-list`, then `storybook-show`: the agent copies an approved snippet and passes only the arguments its API section shows.
2. No pattern fits: `storybook-instructions`, then it writes a story in `app/Stories`.
3. `storybook-test`: failures come back with the story line that caused them, for example `Error: Call to a member function upper() on string at app/Stories/StatusStories.php:29`. It fixes and re-runs until everything passes.

## Write a story

```php
namespace App\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class TinStories extends Stories
{
    public function title(): string
    {
        return 'Forms/TIN';
    }

    public function description(): string
    {
        return 'Philippine tax identification number on supplier and customer forms.';
    }

    public function default(): TextInput
    {
        return TextInput::make('tin')->mask('999-999-999-99999');
    }
}
```

The return type picks the canvas: a schema component (field, entry, layout), a table column, or an action. Column and entry stories take sample data from `records()`.

The package ships shared patterns in `src/Stories`. Each app adds its own in `app/Stories`.

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
