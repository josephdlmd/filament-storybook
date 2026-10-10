<?php

namespace Josephdlmd\FilamentStorybook\Pages;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
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
        return $this->current()?->title() ?? 'Storybook';
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
            Text::make($manifest['description'])->visible(filled($manifest['description'])),
            ...array_map(fn (array $story): Section => Section::make($story['label'])
                ->description($story['description'] ?: null)
                ->schema([
                    $story['kind'] === null
                        ? Callout::make('Not a story')->description('Declare a Filament component, column or action as the return type.')->danger()
                        : Livewire::make(StoryCanvas::class, ['stories' => $stories::class, 'story' => $story['name']])
                            ->key("{$manifest['id']}.{$story['name']}"),
                    // shortcut: plain <pre> because CodeEntry needs phiki/phiki; switch if apps install it.
                    Html::make(new HtmlString('<pre style="overflow-x: auto; font-size: 0.8125rem;"><code>'.e($story['snippet']).'</code></pre>')),
                ]), $manifest['stories']),
        ]);
    }

    protected function current(): ?Stories
    {
        return $this->component === null ? null : app(StoryRegistry::class)->find($this->component);
    }
}
