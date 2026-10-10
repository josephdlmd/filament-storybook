<?php

namespace Josephdlmd\FilamentStorybook\Pages;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Livewire\Attributes\Url;

/**
 * The catalogue: one component's stories at a time, each rendered live beside the code that builds it.
 */
class Storybook extends Page
{
    // A developer tool: reached by URL or an agent's preview link, never from users' navigation.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'storybook';

    #[Url]
    public ?string $component = null;

    /** The panel this page is registered on, for code that runs outside a panel request, like the MCP tools. */
    public static function panel(): ?Panel
    {
        return collect(Filament::getPanels())->first(fn (Panel $panel): bool => in_array(static::class, $panel->getPages(), true));
    }

    public static function canAccess(): bool
    {
        return ! app()->isProduction();
    }

    public function mount(): void
    {
        $this->component ??= array_key_first(app(StoryRegistry::class)->all());
    }

    public function getTitle(): string | Htmlable
    {
        // The group already shows in the sub-navigation.
        return Str::afterLast($this->current()?->title() ?? 'Storybook', '/');
    }

    /**
     * @return array<NavigationItem>
     */
    public function getSubNavigation(): array
    {
        return collect(app(StoryRegistry::class)->all())
            ->map(fn (Stories $stories, string $id): NavigationItem => NavigationItem::make(Str::afterLast($stories->title(), '/'))
                ->group(Str::contains($stories->title(), '/') ? Str::beforeLast($stories->title(), '/') : null)
                ->url(static::getUrl(['component' => $id]))
                ->isActiveWhen(fn (): bool => $this->component === $id))
            ->values()
            ->all();
    }

    public function content(Schema $schema): Schema
    {
        $stories = $this->current();

        if ($stories === null) {
            return $schema->components([
                Text::make('No stories yet. Add a class extending '.Stories::class.' to app/Stories.'),
            ]);
        }

        $manifest = app(StoryRegistry::class)->manifest($stories);

        return $schema->components([
            Text::make(static::prose($manifest['description']))->visible(filled($manifest['description'])),
            ...array_map(fn (array $story): Section => Section::make($story['label'])
                ->description($story['description'] ? static::prose($story['description']) : null)
                ->schema([
                    $story['kind'] === null
                        ? Callout::make('Not a story')->description('Declare a Filament component, column or action as the return type.')->danger()
                        : Livewire::make(StoryCanvas::class, ['stories' => $stories::class, 'story' => $story['name']])
                            ->key("{$manifest['id']}.{$story['name']}"),
                    // shortcut: plain <pre> because CodeEntry needs phiki/phiki; switch if apps install it.
                    // currentColor tints keep the box readable in light and dark mode without a stylesheet.
                    Html::make(new HtmlString('<pre style="overflow-x: auto; font-size: 0.8125rem; padding: 0.75rem 1rem; border-radius: 0.5rem; background: color-mix(in oklab, currentColor 4%, transparent); border: 1px solid color-mix(in oklab, currentColor 12%, transparent);"><code>'.e($story['snippet']).'</code></pre>')),
                ]), $manifest['stories']),
        ]);
    }

    /** Descriptions may use `code` spans, so `->badge()` reads as code instead of the font's arrow ligature. */
    protected static function prose(string $text): HtmlString
    {
        return new HtmlString(Str::inlineMarkdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]));
    }

    protected function current(): ?Stories
    {
        return $this->component === null ? null : app(StoryRegistry::class)->find($this->component);
    }
}
