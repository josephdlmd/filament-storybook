<?php

namespace Josephdlmd\FilamentStorybook\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
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
        // An unsaved record from the first sample row, so stories can lock on `$record` as a resource would.
        $schema->statePath('data')->record($this->record());

        return match ($this->kind()) {
            'component' => $schema->components([$this->build()]),
            'action' => $schema->components([Actions::make([$this->build()])->key('story')]),
            'schema' => $this->build($schema),
            default => $schema,
        };
    }

    public function table(Table $table): Table
    {
        // Set before a table story runs, so the story can still choose its own pagination. Rows are records, as in a resource,
        // so record actions such as `DeleteAction` get the model they expect.
        $table->records(fn (): array => collect($this->instance()->records())
            ->map(fn (array $row, int|string $key): Model => tap($this->model([(new class extends Model {})->getKeyName() => $key, ...$row]), fn (Model $record) => $record->exists = true))
            ->all())->paginated(false);

        return match ($this->kind()) {
            'column' => $table->columns([$this->build()]),
            'table' => $this->build($table),
            default => $table,
        };
    }

    /** The canvas has no Edit page for `EditAction` to open, as a resource page does. */
    public function getDefaultActionUrl(Action $action): ?string
    {
        return $action instanceof EditAction ? '#' : null;
    }

    protected function record(): Model
    {
        return $this->model(Arr::first($this->instance()->records()) ?? []);
    }

    /** @param  array<string, mixed>  $attributes */
    protected function model(array $attributes): Model
    {
        $class = $this->instance()->model();

        return ($class === null ? new class extends Model {} : new $class)->forceFill($attributes);
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
