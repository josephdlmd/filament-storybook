<?php

namespace Josephdlmd\FilamentStorybook\Livewire;

use Closure;
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
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Livewire\Attributes\Locked;
use Livewire\Component;
use ReflectionClass;
use ReflectionParameter;

/**
 * Renders one story in its own Livewire component, so stories never share state:
 * Storybook's iframe, in Filament terms.
 */
class StoryCanvas extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas {
        loadDeferredSchema as protected loadDeferredSchemaNow;
    }
    use InteractsWithTable {
        loadTable as protected loadTableNow;
    }

    #[Locked]
    public string $stories;

    #[Locked]
    public string $story;

    /** Which of Stories::STATES the story is shown in; the canvas switches it, so the story keeps one snippet. */
    #[Locked]
    public string $state = 'populated';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function boot(): void
    {
        // Sample rows have no policy: the catalogue lets every check on them, or on the story's example model, pass, so a
        // permission check (`authorizeIndividualRecords('update')`, `authorize('delete')`) shows the action working.
        Gate::before(fn (mixed $user, string $ability, array $arguments): ?bool => ($record = $arguments[0] ?? null) instanceof Model
            && ((new ReflectionClass($record))->isAnonymous() || is_a($record, (string) $this->instance()->model())) ? true : null);

        // An app story's model is the app's real one, and a sample row's id is a real row's: refuse every write to it for
        // this request, so a preview's Delete never deletes that row. The package's own models write to the in-memory sample database.
        $class = $this->instance()->model();

        if ($class !== null && (new $class)->getConnectionName() !== 'storybook') {
            $class::saving(fn (): bool => false);
            $class::deleting(fn (): bool => false);
        }
    }

    public function mount(): void
    {
        // Entries and fields show the first sample record.
        $this->form->fill($this->state === 'empty' ? [] : Arr::first($this->instance()->records()) ?? []);
    }

    /** Loading holds: the table stays deferred, so its busy indicator shows. */
    public function loadTable(): void
    {
        if ($this->state !== 'loading') {
            $this->loadTableNow();
        }
    }

    /** Loading holds for a deferred schema too (Filament's internal loader, which the canvas only delays). */
    public function loadDeferredSchema(string $schemaKey): void
    {
        if ($this->state !== 'loading') {
            $this->loadDeferredSchemaNow($schemaKey);
        }
    }

    public function form(Schema $schema): Schema
    {
        // An unsaved record from the first sample row, so stories can lock on `$record` as a resource would.
        $schema->statePath('data')->record($this->record());

        return match ($this->kind()) {
            'component' => $schema->components([$this->build()]),
            'action' => $schema->components([Actions::make([$this->build()])->key('story')]),
            'schema' => $this->build($schema)->deferLoading($this->state === 'loading'),
            default => $schema,
        };
    }

    public function table(Table $table): Table
    {
        // Set before a table story runs, so the story can still choose its own pagination. Rows are records, as in a resource,
        // so record actions such as `DeleteAction` get the model they expect.
        // A story without sample rows sets its own query, as a report does, so its totals and filters run on real rows.
        if ($this->instance()->records() !== []) {
            $table->records(fn (): array => collect($this->instance()->records())
                ->map(fn (array $row, int|string $key): Model => tap($this->model([(new class extends Model {})->getKeyName() => $key, ...$row]), fn (Model $record) => $record->exists = true))
                ->all());
        }

        $table->paginated(false);

        $table = match ($this->kind()) {
            'column' => $table->columns([$this->build()]),
            'table' => $this->build($table),
            default => $table,
        };

        // Empty: no rows, from the story's query or its sample rows. Loading: deferred, and held by loadTable().
        if ($this->state === 'empty') {
            $table->hasQuery() ? $table->modifyQueryUsing(fn (Builder $query): Builder => $query->whereRaw('1 = 0')) : $table->records(fn (): array => []);
        }

        return $table->deferLoading($this->state === 'loading');
    }

    /** The canvas has no Edit page for `EditAction` to open, as a resource page does. */
    public function getDefaultActionUrl(Action $action): ?string
    {
        return $action instanceof EditAction ? '#' : null;
    }

    /** A resource page checks a bulk delete against the resource's policy; sample rows have none. */
    public function getDefaultActionIndividualRecordAuthorizationResponseResolver(Action $action): ?Closure
    {
        return fn (): Response => Response::allow();
    }

    /** Empty is a new record with nothing yet, so its notes, history and related lists are empty too. */
    protected function record(): Model
    {
        return $this->model($this->state === 'empty' ? [] : Arr::first($this->instance()->records()) ?? []);
    }

    /** @param  array<string, mixed>  $attributes */
    protected function model(array $attributes): Model
    {
        $class = $this->instance()->model();

        // A sample row has no table behind it: saving or deleting it does nothing, so an action shows how it ends (its notification).
        $model = ($class === null ? new class extends Model
        {
            protected $guarded = [];

            public function save(array $options = []): bool
            {
                return true;
            }

            public function delete(): bool
            {
                return true;
            }
        } : new $class)->forceFill($attributes)->syncOriginal();

        // A sample row with an id stands for a saved record, loaded from the database, as Filament's checks on the original id expect.
        $model->exists = array_key_exists($model->getKeyName(), $attributes);

        return $model;
    }

    public function kind(): ?string
    {
        return Stories::kind($this->instance()->stories()[$this->story] ?? abort(404));
    }

    protected function instance(): Stories
    {
        return app(StoryRegistry::class)->findByClass($this->stories) ?? abort(404);
    }

    /** A story that takes `$state` shows the states the canvas can't make by itself, such as a dashboard with nothing yet. */
    protected function build(mixed ...$arguments): mixed
    {
        $story = $this->instance()->stories()[$this->story];

        if (collect($story->getParameters())->contains(fn (ReflectionParameter $parameter): bool => $parameter->getName() === 'state')) {
            $arguments['state'] = $this->state;
        }

        return $this->instance()->{$this->story}(...$arguments);
    }

    public function render(): View
    {
        return view('filament-storybook::story-canvas');
    }
}
