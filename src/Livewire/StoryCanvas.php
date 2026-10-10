<?php

namespace Josephdlmd\FilamentStorybook\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Renders one story in its own Livewire component, so stories never share state:
 * Storybook's iframe, in Filament terms.
 */
class StoryCanvas extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    #[Locked]
    public string $stories;

    #[Locked]
    public string $story;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        // Entries and fields show the first sample record.
        $this->form->fill(Arr::first($this->instance()->records()) ?? []);
    }

    public function form(Schema $schema): Schema
    {
        $schema->statePath('data');

        return match ($this->kind()) {
            'component' => $schema->components([$this->build()]),
            'action' => $schema->components([Actions::make([$this->build()])]),
            'schema' => $this->build($schema),
            default => $schema,
        };
    }

    public function table(Table $table): Table
    {
        // Set before a table story runs, so the story can still choose its own pagination.
        $table->records(fn (): array => $this->instance()->records())->paginated(false);

        return match ($this->kind()) {
            'column' => $table->columns([$this->build()]),
            'table' => $this->build($table),
            default => $table,
        };
    }

    public function kind(): ?string
    {
        return Stories::kind($this->instance()->stories()[$this->story] ?? abort(404));
    }

    protected function instance(): Stories
    {
        return app(StoryRegistry::class)->findByClass($this->stories) ?? abort(404);
    }

    protected function build(mixed ...$arguments): mixed
    {
        return $this->instance()->{$this->story}(...$arguments);
    }

    public function render(): View
    {
        return view('filament-storybook::story-canvas');
    }
}
