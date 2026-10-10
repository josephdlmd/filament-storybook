# Filament Storybook

Storybook's ideas, rebuilt for native Filament components, so AI agents build UI from your approved patterns instead of guessing.

| Storybook | Here |
|---|---|
| CSF file: meta + named exports | A class extending `Stories`: `title()`, `description()`, one public method per story |
| Renderer + iframe | `StoryCanvas`: each story in its own Livewire component, with real state and actions |
| Docs page + sidebar | The **Storybook** panel page: live story beside its PHP snippet, grouped by title |
| `components.json` manifest | `StoryRegistry::manifest()`: imports, snippets and docblocks, read by reflection |
| `@storybook/addon-mcp` docs tools | `storybook-list` and `storybook-show`, added to the Laravel Boost MCP server |
| `test-run` | A Pest test that renders every story |
| AGENTS.md instructions | `resources/boost/guidelines/core.blade.php`, loaded by Boost |

## Install

Not on Packagist; add the repository first:

```bash
composer config repositories.filament-storybook vcs https://github.com/josephdlmd/filament-storybook
composer require josephdlmd/filament-storybook:^0.1
```

Register the plugin in the panel provider (the page is hidden in production):

```php
->plugin(\Josephdlmd\FilamentStorybook\StorybookPlugin::make())
```

Add `"josephdlmd/filament-storybook"` to `packages` in `boost.json`, then run `php artisan boost:update` so agents get the guideline.

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

The package ships shared DL Group patterns in `src/Stories`. Each app adds its own in `app/Stories`.

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
