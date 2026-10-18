<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Josephdlmd\FilamentStorybook\Accessibility;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[IsReadOnly]
class AuditPages extends Tool
{
    protected string $name = 'storybook-audit';

    protected string $description = 'Render the app\'s real resource pages (list, create, edit, view), their related lists, the panel\'s own pages, the dashboard\'s widgets and the menu, signed in as a panel user, and report render errors, house-rule breaks and WCAG 2.2 AA issues on each. Run it after building or changing a resource screen. Fix every issue and run it again until none remain.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'resources' => $schema->array()->items($schema->string())->description('Resource class names, e.g. "SupplierResource". Omit to audit every resource.'),
            'as' => $schema->string()->description('Email of the user to sign in as. Defaults to the first user who can access the panel.'),
            'panel' => $schema->string()->description('Id of the panel to audit, e.g. "warehouse". Defaults to the panel the Storybook page is on; audit each panel in turn.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $panels = Filament::getPanels();
        $panel = filled($id = $request->get('panel')) ? ($panels[$id] ?? null) : (Storybook::panel() ?? Filament::getDefaultPanel());

        if ($panel === null) {
            return Response::error("No panel \"{$id}\". The panels are: ".implode(', ', array_keys($panels)).'.');
        }

        Filament::setCurrentPanel($panel);

        $user = $this->user($panel, $request->get('as'));

        if ($user === null) {
            return Response::error('No user can access the panel to audit it as. Seed one, or pass "as" with an email.');
        }

        Filament::auth()->setUser($user);

        $resources = collect($panel->getResources())
            ->when($request->get('resources'), fn ($all, array $names) => $all->filter(fn (string $resource): bool => in_array(class_basename($resource), $names, true)));

        $passing = [];
        $failing = [];
        $skipped = [];
        $warnings = [];
        $rendered = [];

        // A panel without the plugin misses the house settings made per panel, though the app-wide defaults still reach it.
        if (! $panel->hasPlugin('filament-storybook')) {
            $warnings[] = "- `panel`: the {$panel->getId()} panel has no `StorybookPlugin`, so it lacks the house settings made per panel: the idle-session warning (WCAG 2.2.1), the unsaved-changes alert and full-width pages (Carbon 2x grid); add `->plugin(StorybookPlugin::make())` to it, with the same options as the other panels.";
        }

        // Renders one page and files it under passing, failing or skipped. A page that sends the user back to itself as it
        // opens, such as a list that lands on the user's last saved filters, is followed once, as the browser would.
        $audit = function (string $key, string $class, array $parameters, Closure $url) use (&$passing, &$failing, &$skipped, &$warnings, &$rendered, $user): void {
            try {
                $page = Livewire::test($class, $parameters);
                // A redirect while mounting aborts with a plain redirect response, which the harness keeps but doesn't expose.
                $response = $page->instance() === null ? (fn () => $this->lastState->getResponse())->call($page) : null;
                $redirect = $response?->isRedirect() ? $response->headers->get('Location') : null;

                if (is_string($redirect) && parse_url($redirect, PHP_URL_PATH) === parse_url(rescue($url, '', report: false), PHP_URL_PATH)) {
                    parse_str((string) parse_url($redirect, PHP_URL_QUERY), $query);
                    $page = Livewire::withQueryParams($query)->test($class, $parameters);
                }

                // Livewire's test harness returns no component when the page refuses or redirects while opening.
                if ($page->instance() === null) {
                    $skipped[] = "- `{$key}`: ".static::userLabel($user).' may not open it; pass "as" with a user who can.';

                    return;
                }

                $rendered[$key] = $page->html();
                $warnings = [...$warnings, ...static::warnings($key, $page->instance())];
                $rendered = [...$rendered, ...static::states($key, $page)];
                $passing[] = "- `{$key}`";
            } catch (HttpException $e) {
                $skipped[] = "- `{$key}`: ".static::userLabel($user)." may not open it ({$e->getStatusCode()}); pass \"as\" with a user who can.";
            } catch (Throwable $e) {
                while ($e->getPrevious() !== null) {
                    $e = $e->getPrevious();
                }

                if ($e instanceof BindingResolutionException) {
                    $skipped[] = "- `{$key}`: it needs route parameters the audit can't supply; open it in the browser to check it.";
                } else {
                    $failing[] = "- `{$key}`: ".static::failure($e);
                }
            }
        };

        foreach ($resources as $resource) {
            foreach ($resource::getPages() as $name => $registration) {
                $key = class_basename($registration->getPage());
                $parameters = [];

                // Edit, View and any custom page of one record, such as a reconciliation, open on the first record.
                if (in_array(InteractsWithRecord::class, class_uses_recursive($registration->getPage()), true)) {
                    $record = $resource::getEloquentQuery()->first();

                    if ($record === null) {
                        $skipped[] = "- `{$key}`: no record to open; create one to audit this page.";

                        continue;
                    }

                    $parameters['record'] = $record->getRouteKey();
                }

                $audit($key, $registration->getPage(), $parameters, fn (): string => $resource::getUrl($name, $parameters));
            }

            // Related lists render inside the View or Edit page; read them from the resource, since a View page may embed them itself.
            $owner = $resource::getEloquentQuery()->first();
            $pageClass = ($resource::getPages()['view'] ?? $resource::getPages()['edit'] ?? null)?->getPage();

            foreach ($owner === null || $pageClass === null ? [] : static::relationManagers($resource::getRelations()) as $manager) {
                if (! $manager::canViewForRecord($owner, $pageClass)) {
                    continue;
                }

                $key = class_basename($manager);

                try {
                    $list = Livewire::test($manager, ['ownerRecord' => $owner, 'pageClass' => $pageClass]);
                    $rendered[$key] = $list->html();
                    $warnings = [...$warnings, ...static::warnings($key, $list->instance())];
                    $passing[] = "- `{$key}`";
                } catch (Throwable $e) {
                    $failing[] = "- `{$key}`: ".static::failure($e);
                }
            }
        }

        // The panel's own pages, such as a dashboard or a settings page, on a full audit; the Storybook page is the workshop, not the app.
        foreach ($request->get('resources') ? [] : $panel->getPages() as $class) {
            if ($class !== Storybook::class) {
                $audit(class_basename($class), $class, [], fn (): string => $class::getUrl());
            }
        }

        // The panel's dashboard widgets, on a full audit. Filament's stats and chart widgets reload every 5 seconds unless told not to.
        foreach ($request->get('resources') ? [] : $panel->getWidgets() as $widget) {
            $widget = $widget instanceof WidgetConfiguration ? $widget->widget : $widget;
            $key = class_basename($widget);

            if (! $widget::canView()) {
                continue;
            }

            if (property_exists($widget, 'pollingInterval') && ($every = (new ReflectionProperty($widget, 'pollingInterval'))->getDefaultValue()) !== null) {
                $warnings[] = "- `{$key}`: it reloads itself every {$every}, and the user can't stop it; set `protected ?string \$pollingInterval = null;`, as the `dashboards-key-figures-and-charts` widgets do (WCAG 2.2.2).";
            }

            $datasets = is_subclass_of($widget, ChartWidget::class) ? (rescue(fn (): mixed => (new ReflectionMethod($widget, 'getData'))->invoke(new $widget), [], report: false)['datasets'] ?? []) : [];

            if (count($datasets) > 1 && collect($datasets)->contains(fn (array $series): bool => blank($series['borderColor'] ?? null) && blank($series['backgroundColor'] ?? null))) {
                $warnings[] = "- `{$key}`: its ".count($datasets).' series share one colour, since Filament paints every series in the widget\'s colour; give each its own `borderColor` and `backgroundColor` from Filament\'s palette, meeting 3:1 on the light and dark backgrounds, and its own line style, as `CashFlowChart` does (Carbon color palettes; WCAG 1.4.1, 1.4.11).';
            }

            if (count($datasets) === 1 && static::showsLegend($widget)) {
                $warnings[] = "- `{$key}`: it has one series but shows a legend; hide it with `'plugins' => ['legend' => ['display' => false]]` (Carbon legends: \"Remove legends … when only one data category is needed\").";
            }

            if (is_subclass_of($widget, ChartWidget::class) && static::hasClickableLegend($widget)) {
                $warnings[] = "- `{$key}`: its legend hides a series on a mouse click, which the keyboard can't do; set `'plugins' => ['legend' => ['onClick' => null]]` in `getOptions()`, and filter with the dashboard's filters instead (WCAG 2.1.1; Carbon legends).";
            }

            try {
                $rendered[$key] = Livewire::test($widget)->html();
                $passing[] = "- `{$key}`";
            } catch (Throwable $e) {
                $failing[] = "- `{$key}`: ".static::failure($e);
            }
        }

        // The menu, on a full audit. Building it runs the app's own code, such as visibility checks, so an error is reported like a page's.
        if (! $request->get('resources')) {
            try {
                $warnings = [...$warnings, ...static::navigationWarnings($panel)];
            } catch (Throwable $e) {
                $failing[] = '- `navigation`: '.static::failure($e);
            }
        }

        // The page around them once, as the browser gets it: the title, language, skip link, landmarks and the menu.
        $layout = $request->get('resources') ? [] : rescue(fn (): array => ['layout' => static::layout($panel)], [], report: false);

        [$issues, $unchecked] = $rendered === [] ? [[], null] : Accessibility::check($rendered, $layout, $panel);

        $text = '# '.count($passing).' pages rendered, '.count($failing).' failing, '.count($warnings).' warnings, '.count($issues)." accessibility issues\n\n"
            .'Signed in as '.static::userLabel($user).".\n".($unchecked ? "\n{$unchecked}\n" : '');

        foreach (['Failing' => $failing, 'Accessibility' => $issues, 'Warnings' => $warnings, 'Skipped' => $skipped, 'Passing' => $passing] as $heading => $lines) {
            if ($lines !== []) {
                $text .= "\n## {$heading}\n\n".implode("\n", $lines)."\n";
            }
        }

        return Response::text($text.($failing === [] && $issues === [] && $warnings === [] ? '' : "\nFix each one, then run storybook-audit again until none remain.\n"));
    }

    /** A chart whose legend shows, Chart.js's default. */
    protected static function showsLegend(string $widget): bool
    {
        $options = rescue(fn (): mixed => (new ReflectionMethod($widget, 'getOptions'))->invoke(new $widget), false, report: false) ?? [];

        return is_array($options) && (($options['plugins']['legend'] ?? [])['display'] ?? true) !== false;
    }

    /** A chart whose legend shows and still toggles a series on click, Chart.js's default. */
    protected static function hasClickableLegend(string $widget): bool
    {
        // No options is Chart.js's defaults; options written as JavaScript can't be read, so they are left alone.
        $options = rescue(fn (): mixed => (new ReflectionMethod($widget, 'getOptions'))->invoke(new $widget), false, report: false) ?? [];
        $legend = is_array($options) ? ($options['plugins']['legend'] ?? []) : [];

        return is_array($options) && ($legend['display'] ?? true) !== false && ! array_key_exists('onClick', $legend);
    }

    /**
     * The page's other states, each checked like the page itself: each header action's modal open, and a create form
     * submitted empty. Opening a modal runs nothing; the form is submitted only when it is known to fail, and anything
     * written is rolled back all the same.
     *
     * @return array<string, string>
     */
    protected static function states(string $key, Testable $page): array
    {
        $states = [];

        foreach (static::flatten($page->instance()->getCachedHeaderActions()) as $action) {
            if ($action->isHidden() || $action->isDisabled() || $action->getUrl() !== null || ! $action->shouldOpenModal()) {
                continue;
            }

            // Filament sends an opened modal as a partial of its own; forcing a whole render puts it in the page, as the browser shows it.
            $states["{$key}, {$action->getName()} open"] = rescue(fn (): string => $page->call('mountAction', $action->getName())->call('forceRender')->html(), '', report: false);
            rescue(fn () => $page->call('unmountAction'), report: false);
        }

        if ($page->instance() instanceof CreateRecord) {
            try {
                $page->instance()->form->validate();
            } catch (ValidationException) {
                DB::beginTransaction();

                try {
                    $states["{$key}, with errors"] = $page->call('create')->html();
                } finally {
                    DB::rollBack();
                }
            }
        }

        return array_filter($states);
    }

    /** A page of the panel, rendered whole in this process, with a session kept in memory so no login is stored. */
    protected static function layout(Panel $panel): string
    {
        $driver = config('session.driver');
        config(['session.driver' => 'array']);

        try {
            $kernel = app(Kernel::class);
            // Any page has the layout; a resource's list is there in every app, where the home may only redirect.
            $url = ($resource = collect($panel->getResources())->first()) ? $resource::getUrl('index', panel: $panel->getId()) : $panel->getUrl();

            // A panel without a dashboard sends its home to the first page.
            for ($hops = 0; $hops < 3; $hops++) {
                $response = $kernel->handle($request = \Illuminate\Http\Request::create($url));
                $kernel->terminate($request, $response);

                if (! $response->isRedirect()) {
                    break;
                }

                $url = $response->headers->get('Location');
            }

            return $response->isOk() ? (string) $response->getContent() : throw new \RuntimeException("{$url} answered {$response->getStatusCode()}");
        } finally {
            config(['session.driver' => $driver]);
        }
    }

    /** The innermost error, which names the actual mistake, with the line to fix. */
    protected static function failure(Throwable $e): string
    {
        while ($e->getPrevious() !== null) {
            $e = $e->getPrevious();
        }

        return class_basename($e).': '.$e->getMessage().' at '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine();
    }

    /**
     * @param  array<class-string|RelationGroup|RelationManagerConfiguration>  $relations
     * @return list<class-string>
     */
    protected static function relationManagers(array $relations): array
    {
        return collect($relations)
            ->flatMap(fn ($relation): array => $relation instanceof RelationGroup ? $relation->getManagers() : [$relation])
            ->map(fn ($manager): string => $manager instanceof RelationManagerConfiguration ? $manager->relationManager : $manager)
            ->values()->all();
    }

    protected static function userLabel(Authenticatable $user): string
    {
        return (string) ($user->email ?? $user->getAuthIdentifier());
    }

    protected function user(Panel $panel, ?string $email): ?Authenticatable
    {
        $model = Filament::auth()->getProvider()->getModel();

        if ($email !== null) {
            return $model::query()->where('email', $email)->first();
        }

        // shortcut: scans users in id order, fine for dev databases; pass "as" in a large one.
        return $model::query()->orderBy((new $model)->getKeyName())->lazy()
            ->first(fn ($user): bool => ! method_exists($user, 'canAccessPanel') || $user->canAccessPanel($panel));
    }

    /**
     * The house rules a real screen can break while still rendering: the page-level versions of the patterns.
     *
     * @return list<string>
     */
    protected static function warnings(string $key, Component $page): array
    {
        $warnings = [];
        $warn = function (string $message) use (&$warnings, $key): void {
            $warnings[] = "- `{$key}`: {$message}";
        };

        // Filament title-cases the model in these ("Create Supplier", "Purchase Orders"); edit and view titles name the record, so they're left alone.
        if ($page instanceof ListRecords || $page instanceof CreateRecord) {
            $resource = $page::getResource();
            $titles = [(string) $page->getTitle(), ...($page instanceof ListRecords ? [$resource::getNavigationLabel(), (string) $resource::getBreadcrumb()] : [])];

            foreach (array_unique($titles) as $title) {
                if (static::isTitleCase($title, $title)) {
                    $warn("\"{$title}\" (the page title, navigation or breadcrumb) is in Title Case; add `use \\Josephdlmd\\FilamentStorybook\\Concerns\\HasSentenceCaseLabels;` inside `".class_basename($resource).'` and set its `$recordTitleAttribute`, or, for a resource whose records have no name, set the page\'s `$title` and the resource\'s `$breadcrumb` (Carbon content).');
                }
            }
        }

        $header = method_exists($page, 'getCachedHeaderActions') ? $page->getCachedHeaderActions() : [];
        $headerActions = static::flatten($header);
        $tableActions = [];

        $primary = collect($header)
            ->filter(fn ($action): bool => $action instanceof Action && $action->isVisible() && in_array($action->getColor() ?? 'primary', ['primary'], true));

        if ($primary->count() > 1) {
            $warn("{$primary->count()} primary actions in the header; keep one primary and make the rest `->color('gray')` or put them in an `ActionGroup` (Carbon button).");
        }

        $shown = collect($header)->filter(fn ($action): bool => ($action instanceof Action || $action instanceof ActionGroup) && $action->isVisible());

        if ($shown->count() > 3) {
            $warn("{$shown->count()} buttons in the header; show at most three and put the rest in a labelled menu, `ActionGroup::make([...])->label('More')->button()` (Carbon button: \"Any more than three actions should be grouped meaningfully using menu buttons\").");
        }

        if (method_exists($page, 'getTable') && $page->getTable()->getColumns() !== []) {
            $table = $page->getTable();
            $visible = collect($table->getColumns())->reject(fn (Column $column): bool => $column->isHidden() || $column->isToggledHiddenByDefault());

            foreach ($visible as $column) {
                if (static::isTitleCase((string) $column->getLabel(), $column->getName())) {
                    $warn("the column \"{$column->getLabel()}\" is in Title Case; write it in sentence case, capitalising only the first word, names and acronyms (Carbon data table).");
                }

                if ($column->hasTooltip()) {
                    $warn("the column `{$column->getName()}` has a tooltip, which shows on mouse hover only; put the text in the column's `description()` or on the record's page (WCAG 2.1.1; Carbon tooltip: on a trigger reached by Tab).");
                }

                if ($column instanceof TextColumn && $column->isMoney() && ! in_array($column->getAlignment(), [Alignment::End, Alignment::Right], true)) {
                    $warn("the money column `{$column->getName()}` must be right-aligned with `->alignEnd()`, as `tables-money-column` does (USWDS table).");
                }
            }

            if ($visible->count() < 3) {
                $warn("the table shows {$visible->count()} column(s); a table needs three or more, so show these as a list of labels and values, as `records-details` does (Carbon data table: \"Tables require three or more columns\").");
            }

            $toolbar = collect([...$header, ...$table->getHeaderActions(), ...$table->getToolbarActions()])->filter(fn ($action): bool => ($action instanceof Action || $action instanceof ActionGroup) && $action->isVisible());

            if ($toolbar->count() > 5) {
                $warn("{$toolbar->count()} actions above the table; keep five at most and put the rest in a menu (Carbon data table: \"Include up to five actions within the table toolbar\").");
            }

            if ($table->hasReorderableColumns()) {
                $warn('its columns reorder only by dragging in the column manager; drop `reorderableColumns()`, and let users hide columns instead, as `tables-list-table` does (WCAG 2.5.7).');
            }

            foreach (collect($table->getToolbarActions())->filter(fn ($action): bool => $action instanceof BulkActionGroup) as $group) {
                if (count($actions = $group->getFlatActions()) <= 5) {
                    $warn('bulk actions are in a "Bulk actions" menu; with five or fewer, put them straight in `toolbarActions([...])`, so they show as buttons once a row is ticked, as `tables-bulk-actions` does (Carbon data table: "Once an item from the table is selected, the batch action bar appears at the top of the table").');
                }
            }

            foreach (collect($table->getRecordActions())->filter(fn ($action): bool => $action instanceof ActionGroup) as $group) {
                if (count($actions = $group->getFlatActions()) < 3) {
                    $warn('a row menu holds only '.count($actions).' action(s); show them inline instead (Carbon data table: "When the overflow menu contains fewer than three options, keep the actions inline").');
                }
            }

            // Counted on each row, as the user sees them: actions shown only on some rows (Send again for an invited user,
            // Edit for an active one) don't add up. With no rows, the actions that show without a record.
            $inline = collect($table->getRecordActions())->filter(fn ($action): bool => $action instanceof Action);
            $records = rescue(fn (): mixed => $table->getRecords(), [], report: false);
            $rows = collect($records instanceof Paginator ? $records->items() : $records)->take(25);
            $shown = $rows->isEmpty()
                ? $inline->filter(fn (Action $action): bool => rescue(fn (): bool => (clone $action)->isVisible(), true, report: false))->count()
                // A copy of each action takes the row's record, so the page's own actions are left as they were.
                : $rows->max(fn ($record): int => $inline->filter(fn (Action $action): bool => rescue(fn (): bool => (clone $action)->record($record)->isVisible(), true, report: false))->count());

            if ($shown > 2) {
                $warn("{$shown} row actions are inline; keep at most two and put the rest in an `ActionGroup` (Carbon data table, NN/g data tables).");
            }

            if (count($table->getFilters()) > 1 && in_array($table->getFiltersLayout(), [FiltersLayout::Dropdown, FiltersLayout::Modal, FiltersLayout::AboveContentCollapsible], true)) {
                // The collapsible layout puts its button alone in a row above the search, with a count even when none apply.
                $warn('several filters are hidden in a dropdown or behind a button; put them above the table, open, with `layout: FiltersLayout::AboveContent` (Carbon filtering: "horizontally at the top of the data set").');
            }

            if ($page instanceof ListRecords && blank($table->getEmptyStateDescription()) && $table->getEmptyStateActions() === []) {
                $warn('the empty list gives no next step; add `emptyStateDescription()` and `emptyStateActions()`, as `tables-list-table` does (Carbon empty states).');
            }

            $emptyActions = collect($table->getEmptyStateActions())->filter(fn ($action): bool => $action instanceof Action && $action->isVisible());

            if ($emptyActions->count() > 1) {
                $warn("the empty list offers {$emptyActions->count()} actions; offer the most important one (Carbon empty states: \"Don't cover multiple options in one empty state\").");
            }

            $tablePrimary = collect($table->getHeaderActions())->contains(fn ($action): bool => $action instanceof Action && $action->isVisible() && ($action->getColor() ?? 'primary') === 'primary');

            if (($primary->isNotEmpty() || $tablePrimary) && $emptyActions->contains(fn (Action $action): bool => ($action->getColor() ?? 'primary') === 'primary' && ! $action->isOutlined())) {
                $warn("the empty list's button is a second primary beside the page's own; make it `->outlined()` (Carbon empty states: \"using a tertiary button for the call to action\").");
            }

            $tableActions = static::flatten([...$table->getHeaderActions(), ...$table->getRecordActions(), ...$table->getToolbarActions()]);
        }

        foreach (collect([...$headerActions, ...$tableActions])->filter(fn (Action $action): bool => $action->isIconButton() && blank($action->getTooltip()))->unique(fn (Action $action): string => $action->getName()) as $action) {
            $warn("the icon-only action `{$action->getName()}` has no tooltip; add `->tooltip('{$action->getLabel()}')`, shown on hover and focus (Carbon tooltip: \"Every icon-only button needs a tooltip\").");
        }

        // A shortcut of one character key fires while the user types or dictates (WCAG 2.1.4); Filament's turn on Ctrl, ⌘ or Alt.
        foreach (collect([...$headerActions, ...$tableActions])->unique(fn (Action $action): string => $action->getName()) as $action) {
            foreach (array_filter($action->getKeyBindings() ?? [], fn (string $binding): bool => ! preg_match('/\b(mod|ctrl|command|meta|alt|option)\+/', $binding)) as $binding) {
                $warn("the action `{$action->getName()}` runs on the key `{$binding}` alone, which fires while the user types or dictates; add a modifier, such as `mod+{$binding}` (WCAG 2.1.4).");
            }
        }

        foreach ([...$headerActions, ...$tableActions] as $action) {
            if (! $action instanceof DeleteAction && ! $action instanceof ForceDeleteAction && ! $action instanceof DeleteBulkAction && ! $action instanceof ForceDeleteBulkAction) {
                continue;
            }

            if (! $action->isConfirmationRequired()) {
                $warn("`{$action->getName()}` deletes without asking; add `->requiresConfirmation()` (WCAG 3.3.4).");
            }

            if (in_array($action, $headerActions, true) && $action->isButton() && ! $action->isOutlined()) {
                $warn("`{$action->getName()}` is a solid danger button beside other actions; make it `->outlined()` and keep solid red for the confirm button, as `actions-delete` does (Carbon button).");
            }
        }

        foreach ([...$headerActions, ...$tableActions] as $action) {
            $label = trim((string) $action->getLabel());
            // A row action here has no record, and asking about its modal runs closures that may type one, such as
            // `modalHeading(fn (Account $record) => …)`; a closure that can't run without its record is left unchecked.
            $verbs = rescue(fn (): array => $action->shouldOpenModal() ? [$label, trim($action->getModalSubmitActionLabel())] : [$label], [$label], report: false);

            if ($vague = collect($verbs)->first(fn (string $verb): bool => (bool) preg_match('/^(ok|okay|yes|done|submit|confirm)$/i', $verb))) {
                $warn("`{$action->getName()}` has a button labelled \"{$vague}\"; say what it does with a verb, such as \"Approve order\" (Carbon modal and forms).");
            }

            // Against the label itself, as a page title is: Filament builds "Create Sales Invoice" from a capitalised model label.
            // A label carrying data, such as "Lock December 2025" or "Open #5", names something and is left alone.
            if (static::isTitleCase($label, $label) && ! preg_match('/\d/', $label)) {
                $warn("the action \"{$label}\" is in Title Case; write it in sentence case (Carbon button).");
            }

            $deletes = $action instanceof DeleteAction || $action instanceof ForceDeleteAction || $action instanceof DeleteBulkAction || $action instanceof ForceDeleteBulkAction;
            $removes = $action instanceof DetachAction || $action instanceof DetachBulkAction || $action instanceof DissociateAction || $action instanceof DissociateBulkAction;

            if (($deletes && ! preg_match('/\bdelete\b/i', $label)) || ($removes && preg_match('/\bdelete\b/i', $label))) {
                $warn("`{$action->getName()}` is labelled \"{$label}\", but "
                    .($deletes ? 'it destroys the record, which Carbon calls Delete; keep "Delete"' : 'the record stays, which Carbon calls Remove; keep "Remove"')
                    .' (Carbon common actions).');
            }

            if (rescue(fn (): bool => $action->isConfirmationRequired() && $action->getModalDescription() === __('filament-actions::modal.confirmation'), false, report: false)) {
                $warn("`{$action->getName()}` asks \"".__('filament-actions::modal.confirmation').'"; say the consequence in `modalDescription()`, such as "Its 3 payments are deleted too. This cannot be undone.", as `actions-delete` does, or drop the confirmation if the action is routine (Carbon dialogs).');
            }
        }

        if ($page instanceof ViewRecord) {
            if ($page->getSchema('infolist')?->getComponents() === []) {
                $warn('it shows the record as a disabled form, which screen readers skip and which needn\'t meet contrast; give the resource an `infolist()` of read-only entries, as `records-view-page` does (Carbon read-only states, WCAG 1.4.3).');
            }

            if (count($page->getCachedRelationManagers()) > 1 || $page->hasCombinedRelationManagerTabsWithContent()) {
                $warn('its related lists are tabs; stack each relation manager in the main column with `Livewire::make()`, as `records-view-page` does, unless they are rarely used (Carbon tabs, NN/g tabs 2024).');
            }
        }

        // A list page reuses the resource form for its create modal, and a view page's form is the disabled one warned about above.
        $fields = $page instanceof ListRecords || $page instanceof ViewRecord ? [] : rescue(fn (): array => $page->form->getFlatFields(withHidden: false), [], report: false);

        if ($fields !== [] && max(array_filter(rescue(fn (): array => $page->form->getColumns(), [], report: false), 'is_int') ?: [1]) > 1) {
            $warn('its form lays fields out in several columns; keep one column and put only fields that belong together side by side, in a `Fieldset` or `Grid`, as `forms-date.range` does (Carbon forms: "Carbon generally recommends single-column forms").');
        }

        // Read-only entries may sit beside their labels (a document's totals, forms-document-totals); a field the user fills in never does.
        // A compact control in a section's heading row may sit beside its label, so the row stays one line (owner, 2026-10-13).
        $inline = collect($fields)->filter(fn ($field): bool => (bool) rescue(fn (): ?bool => $field->hasInlineLabel(), false, report: false) && ! static::inSectionHeading($field));

        if ($inline->isNotEmpty()) {
            $warn('its fields have their labels beside them (`'.$inline->map->getName()->take(3)->implode('`, `').'`'.($inline->count() > 3 ? ', …' : '').'); put labels above their fields, without `inlineLabel()` (Carbon forms: "Top-aligned labels are Carbon\'s default").');
        }

        foreach ($fields as $field) {
            $name = $field->getName();

            if ($field instanceof TextInput && filled($field->getPlaceholder())) {
                $warn("the field `{$name}` uses placeholder(); put the example in helperText(), worded \"For example, …\" (USWDS, GOV.UK).");
            }

            if ($field instanceof TextInput && preg_match('/(^|_)e?mail(_|$)/i', $name) && ! $field->isEmail()) {
                $warn("the field `{$name}` holds an email address but isn't `email()`; add it, with `maxLength(254)` and spellcheck off, as `forms-contact-details` does (GOV.UK email addresses).");
            }

            if ($field instanceof TextInput && preg_match('/(^|_)(phone|mobile|telephone|landline)(_|$)/i', $name) && ! $field->isTel()) {
                $warn("the field `{$name}` holds a phone number but isn't `tel()`; add it, and store the number as +63, as `forms-contact-details.phone` does (GOV.UK telephone numbers; business standards).");
            }

            if (method_exists($field, 'isReadOnly') && $field->isReadOnly() && static::isSaved($field)) {
                $warn("the read-only field `{$name}` is still saved, and its value can be changed in the browser; put it in a group with `->saved(false)`, as `records-locked` does, and set the value on the server (Filament docs).");
            }

            $label = trim((string) $field->getLabel());

            if (static::isTitleCase($label, $name)) {
                $warn("the field label \"{$label}\" is in Title Case; write it in sentence case (Carbon forms).");
            }

            if (str_ends_with($label, ':')) {
                $warn("the field label \"{$label}\" ends with a colon; drop it (Carbon forms).");
            }

            if (filled($field->getHintIconTooltip())) {
                $warn("the field `{$name}` explains itself in a hint icon's tooltip, which shows on mouse hover only; put the text in `helperText()` (WCAG 2.1.1; Carbon form: the info icon \"is a button, which is in the tab order\").");
            }

            if (! $field->isRequired() && ! $field instanceof Checkbox && ! $field instanceof Toggle && ! $field instanceof Hidden && ! (method_exists($field, 'isReadOnly') && $field->isReadOnly()) && ! $field->isDisabled() && filled($label) && ! str_contains($label, '(optional)')) {
                $warn("the optional field \"{$label}\" doesn't say so; label it \"{$label} (optional)\", and leave required fields unmarked (Carbon form: \"The pattern used should be consistent throughout your product\").");
            }

            if ($field instanceof Radio && ! $field->isRequired() && ! collect($field->getOptions())->keys()->merge(array_values($field->getOptions()))->contains(fn ($option): bool => strcasecmp((string) $option, 'none') === 0)) {
                $warn("the radio group `{$name}` is optional but can't be cleared once picked; make it `required()` or add a \"None\" option (Carbon radio button and forms).");
            }

            // A radio group goes side by side with inline(), a checkbox list with columns(); two short radios may (GOV.UK inline radios).
            if (($field instanceof Radio && $field->isInline() && ! static::twoShortOptions($field)) || ($field instanceof CheckboxList && max(array_filter(Arr::wrap($field->getColumns()), 'is_int') ?: [1]) > 1)) {
                $warn("the options of `{$name}` sit side by side; stack them, without `inline()` or `columns()`; only two short options may sit side by side (Carbon checkbox and forms: \"arrange the checkbox and radio button groups vertically\"; GOV.UK radios: inline \"only\" for two short options).");
            }

            // Fixed options only: a relationship's count is how many records exist so far, and a record list grows (forms-record-picker).
            // Options from a closure are taken to be such a list too (a query of accounts), never a fixed set written out.
            // A select in a repeater's row stays one, since radios don't fit a table cell (forms-line-items).
            if ($field instanceof Select && ! $field->hasRelationship() && ! (fn (): bool => $this->options instanceof Closure)->call($field) && ! static::inRepeater($field) && filled($options = rescue(fn (): array => $field->getOptions(), [], report: false)) && count($options) <= 5) {
                $warn($field->isMultiple()
                    ? "the select `{$name}` offers ".count($options).' options to pick several from; show them as a `CheckboxList`, as `forms-choice` does (Carbon forms: a select list for more than five options).'
                    : "the select `{$name}` offers ".count($options).' options; show them as stacked radios, as `forms-choice` does (Carbon forms: "When you have more than five options to present to the user, use a select list").');
            }

            // A repeater directly under a section heading that names it, named by the same words for screen readers (WAI-ARIA APG).
            if ($field->isLabelHidden() && ! static::namedBySectionHeading($field)) {
                $warn("the field `{$name}` hides its label; keep it visible, above the field (Carbon forms).");
            }

            if ($field instanceof RichEditor || $field instanceof MarkdownEditor) {
                $warn("the field `{$name}` is a rich-text or Markdown editor, whose toolbar the keyboard can't reach in Filament 5 (its buttons are `tabindex=\"-1\"`); use a `Textarea` until Filament fixes it (WCAG 2.1.1).");
            }

            if ($field instanceof Toggle) {
                $warn("the field `{$name}` is a toggle in a form that is saved; use a `Checkbox`, as `forms-yes-or-no` does (Carbon toggle and checkbox).");
            }

            if ($field instanceof DateTimePicker && ! $field->isNative()) {
                $warn("the date field `{$name}` uses `native(false)`, which cannot be typed into; use the native picker, as `forms-date` does (Carbon and USWDS date pickers).");
            }

            if ($field instanceof Select && $field->hasRelationship() && ! $field->isSearchable()) {
                $warn("the select `{$name}` lists other records but can't be searched, so it loads all of them; make it `searchable()` by code and name, as `forms-record-picker` does (Carbon dropdown: a combo box when the list may be very long or not predefined).");
            }

            if ($field instanceof Repeater && $field->isReorderableWithDragAndDrop() && ! $field->isReorderableWithButtons()) {
                $warn("`{$name}` reorders only by dragging; add `->reorderableWithButtons()`, or `->reorderable(false)` if the order doesn't matter, as `forms-line-items` does (WCAG 2.5.7).");
            }

            // Wide tables show the column header in place of each field's label, so the two must read the same (WCAG 2.5.3).
            if ($field instanceof Repeater && filled($columns = $field->getTableColumns())) {
                $children = $field->getDefaultChildComponents();
                $cells = collect($children instanceof Schema ? $children->getComponents(withHidden: true) : $children)->reject(fn ($cell): bool => $cell instanceof Hidden)->values();

                foreach ($cells as $index => $cell) {
                    $header = isset($columns[$index]) ? $columns[$index]->getLabel() : null;

                    if ($header !== $cell->getLabel()) {
                        $warn($header === null
                            ? "the line-item field `{$cell->getName()}` has no column in `{$name}`, so Filament doesn't show it; add a `TableColumn` for it, as `forms-line-items` does."
                            : "the column \"{$header}\" in `{$name}` heads the field labelled \"{$cell->getLabel()}\"; make the header the field's label, word for word, as `forms-line-items` does (WCAG 2.5.3).");
                    }
                }
            }

            // Spatie Media Library keeps the original name itself; an image shown as a picture needs none.
            if ($field instanceof FileUpload && ! is_a($field, 'Filament\\Forms\\Components\\SpatieMediaLibraryFileUpload') && ! $field->isAvatar()
                && ! (filled($types = $field->getAcceptedFileTypes()) && collect($types)->every(fn (string $type): bool => str_starts_with($type, 'image/')))
                && blank($field->getFileNamesStatePath(isAbsolute: false))) {
                $warn("the upload `{$name}` shows a file by the random name it is stored under; keep the name it was uploaded with, `storeFileNamesIn()`, as `forms-file-upload` does (Carbon file uploader).");
            }

            if ($field instanceof BaseFileUpload && (blank($field->getAcceptedFileTypes()) || $field->getMaxSize() === null)) {
                $warn("the upload `{$name}` does not state its accepted types and largest size; set both and say them in `helperText()`, as `forms-file-upload` does (USWDS file input).");
            }
        }

        $layouts = $page instanceof ListRecords ? [] : rescue(fn (): array => $page->getSchema($page instanceof ViewRecord ? 'infolist' : 'form')?->getFlatComponents(withHidden: true) ?? [], [], report: false);

        foreach ($layouts as $layout) {
            if ($layout instanceof Section && $layout->isCollapsible() && ! $layout->isCollapsed()) {
                $warn("the section \"{$layout->getHeading()}\" can collapse but starts open; start it collapsed with `->collapsed()`, or don't make it collapsible (Carbon accordion: \"begin by default in the collapsed state\").");
            }

            $heading = match (true) {
                $layout instanceof Section => $layout->getHeading(),
                $layout instanceof Fieldset, $layout instanceof Tab => $layout->getLabel(),
                default => null,
            };

            if (filled($heading) && static::isTitleCase($heading = trim((string) $heading), $heading)) {
                $warn("the heading \"{$heading}\" is in Title Case; write it in sentence case (Carbon content).");
            }
        }

        foreach (static::errorMessages($page) as $message) {
            $warn("the error \"{$message}\" doesn't say what to do; word it like \"Enter name\" or \"Select status\" (GOV.UK error message, NN/g 2023).");
        }

        return $warnings;
    }

    /**
     * The panel's menu as the signed-in user sees it: two levels at most, groups in sentence case, and words that stay on screen.
     *
     * @return list<string>
     */
    protected static function navigationWarnings(Panel $panel): array
    {
        $warnings = [];

        if ($panel->isSidebarCollapsibleOnDesktop()) {
            $warnings[] = '- `navigation`: the sidebar shrinks to icons only, so the menu loses its words; use `->sidebarFullyCollapsibleOnDesktop()`, which hides it whole (NN/g menu design: icons support text labels, never replace them).';
        }

        if (! $panel->hasDarkMode()) {
            $warnings[] = '- `navigation`: dark mode is off; keep Filament\'s light and dark modes and its theme switcher (Carbon color: switching modes "is highly encouraged").';
        }

        foreach ($panel->getNavigation() as $group) {
            foreach ($group->getItems() as $item) {
                $name = trim((string) $item->getLabel());

                if (preg_match('/^(Create|New|Add|Edit|Manage|View|Import|Export)\b/', $name)) {
                    $warnings[] = "- `navigation`: \"{$name}\" names an action; name the place, such as \"Suppliers\" (Carbon UI shell header: \"Avoid using verbs as header links\").";
                }

                if (filled($item->getBadge())) {
                    $warnings[] = "- `navigation`: \"{$name}\" shows a count; drop `getNavigationBadge()`, since a number badge is only for new items that clear once seen (Carbon status indicators).";
                }
            }

            $label = trim((string) $group->getLabel());

            if (static::isTitleCase($label, $label)) {
                $warnings[] = "- `navigation`: the group \"{$label}\" is in Title Case; write it in sentence case (Carbon content).";
            }

            // Inside a group, an item's children are a third level; an ungrouped item and its children are only two.
            foreach (filled($label) ? $group->getItems() : [] as $item) {
                if (filled($item->getChildItems())) {
                    $warnings[] = "- `navigation`: \"{$item->getLabel()}\" in \"{$label}\" has items under it, a third level; make them items of the group, or the page's own sub-navigation (Carbon UI shell left panel: no three tiers; NN/g menu design).";
                }
            }
        }

        return $warnings;
    }

    /**
     * Title Case that someone typed: a label of several words that is its name with every word capitalised, "Due Date" for `due_date`.
     * A heading is its own name, so "Customer Details" is caught and "Notes for Xero" or "TIN details" is not. A false warning costs
     * more than a missed one, so a label mixing cases is left alone.
     */
    public static function isTitleCase(string $label, string $name): bool
    {
        // Two capitalised words at least: "Discount (%)" is one word and its unit.
        return preg_match_all('/\b\p{Lu}/u', $label) > 1 && $label === Str::headline(Str::afterLast($name, '.'));
    }

    /** Whether a field's value reaches the save: neither it nor any group around it is `saved(false)`. */
    protected static function isSaved(?SchemaComponent $component): bool
    {
        for (; $component !== null; $component = $component->getContainer()->getParentComponent()) {
            if (! $component->isDehydrated()) {
                return false;
            }
        }

        return true;
    }

    /** Whether a field sits in a repeater's row. */
    protected static function inRepeater(SchemaComponent $component): bool
    {
        while (($component = $component->getContainer()->getParentComponent()) !== null) {
            if ($component instanceof Repeater) {
                return true;
            }
        }

        return false;
    }

    /**
     * Submits a create page's form empty and keeps the error messages that use wording GOV.UK and NN/g rule out.
     *
     * @return list<string>
     */
    protected static function errorMessages(Component $page): array
    {
        if (! $page instanceof CreateRecord) {
            return [];
        }

        try {
            $page->form->validate();
        } catch (ValidationException $e) {
            return collect($e->errors())->flatten()
                ->filter(fn (string $message): bool => (bool) preg_match('/\b(is required|invalid|incorrect|please|sorry)\b/i', $message))
                ->unique()->values()->all();
        }

        return [];
    }

    /**
     * @param  array<Action|ActionGroup>  $actions
     * @return list<Action>
     */
    protected static function flatten(array $actions): array
    {
        return collect($actions)
            ->flatMap(fn ($action): array => $action instanceof ActionGroup ? $action->getFlatActions() : [$action])
            ->filter(fn ($action): bool => $action instanceof Action)
            ->values()->all();
    }
    /**
     * Whether a field sits in a section's heading row (`Section::afterHeader()`).
     */
    protected static function inSectionHeading(mixed $field): bool
    {
        $container = rescue(fn () => $field->getContainer(), null, report: false);
        $section = $container?->getParentComponent();

        return $section instanceof Section && $container === $section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY);
    }

    /**
     * Whether a radio group has two options of one or two words each, the only ones that may sit side by side.
     */
    protected static function twoShortOptions(Radio $field): bool
    {
        $options = rescue(fn (): array => $field->getOptions(), [], report: false);

        return count($options) === 2 && collect($options)->every(fn ($label): bool => str_word_count(strip_tags((string) $label)) <= 2);
    }

    /**
     * Whether a repeater leaves its label off for the section heading right above it, named by `aria-label` instead.
     */
    protected static function namedBySectionHeading(mixed $field): bool
    {
        $section = rescue(fn () => $field->getContainer()?->getParentComponent(), null, report: false);

        return $field instanceof Repeater && $section instanceof Section && filled($section->getHeading()) && filled($field->getExtraAttributes()['aria-label'] ?? null);
    }
}
