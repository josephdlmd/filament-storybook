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
        return $schema
            ->statePath('data')
            ->components(match ($this->kind()) {
                'schema' => [$this->build()],
                'action' => [Actions::make([$this->build()])],
                default => [],
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->instance()->records())
            ->columns($this->kind() === 'column' ? [$this->build()] : [])
            ->paginated(false);
    }

    public function kind(): ?string
    {
        return Stories::kind($this->instance()->stories()[$this->story] ?? abort(404));
    }

    protected function instance(): Stories
    {
        return app(StoryRegistry::class)->findByClass($this->stories) ?? abort(404);
    }

    protected function build(): mixed
    {
        return $this->instance()->{$this->story}();
    }

    public function render(): View
    {
        return view('filament-storybook::story-canvas');
    }
}
