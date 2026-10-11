<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
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
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Josephdlmd\FilamentStorybook\Accessibility;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[IsReadOnly]
class AuditPages extends Tool
{
    protected string $name = 'storybook-audit';

    protected string $description = 'Render the app\'s real resource pages (list, create, edit, view), their related lists, the dashboard\'s widgets and the menu, signed in as a panel user, and report render errors, house-rule breaks and WCAG 2.2 AA issues on each. Run it after building or changing a resource screen. Fix every issue and run it again until none remain.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'resources' => $schema->array()->items($schema->string())->description('Resource class names, e.g. "SupplierResource". Omit to audit every resource.'),
            'as' => $schema->string()->description('Email of the user to sign in as. Defaults to the first user who can access the panel.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $panel = Storybook::panel() ?? Filament::getDefaultPanel();
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

        foreach ($resources as $resource) {
            foreach ($resource::getPages() as $name => $registration) {
                $key = class_basename($registration->getPage());
                $parameters = [];

                if (in_array($name, ['edit', 'view'], true)) {
                    $record = $resource::getEloquentQuery()->first();

                    if ($record === null) {
                        $skipped[] = "- `{$key}`: no record to open; create one to audit this page.";

                        continue;
                    }

                    $parameters['record'] = $record->getRouteKey();
                }

                try {
                    $page = Livewire::test($registration->getPage(), $parameters);

                    // Livewire's test harness returns no component when the page refuses or redirects while opening.
                    if ($page->instance() === null) {
                        $skipped[] = "- `{$key}`: ".static::userLabel($user).' may not open it; pass "as" with a user who can.';

                        continue;
                    }

                    $rendered[$key] = $page->html();
                    $warnings = [...$warnings, ...static::warnings($key, $page->instance())];
                    $passing[] = "- `{$key}`";
                } catch (HttpException $e) {
                    $skipped[] = "- `{$key}`: ".static::userLabel($user)." may not open it ({$e->getStatusCode()}); pass \"as\" with a user who can.";
                } catch (Throwable $e) {
                    while ($e->getPrevious() !== null) {
                        $e = $e->getPrevious();
                    }

                    if ($e instanceof BindingResolutionException) {
                        $skipped[] = "- `{$key}`: it needs route parameters the audit can't supply; open it in the browser to check it.";

                        continue;
                    }

                    $failing[] = "- `{$key}`: ".static::failure($e);
                }
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

        [$issues, $unchecked] = $rendered === [] ? [[], null] : Accessibility::check($rendered);

        $text = '# '.count($passing).' pages rendered, '.count($failing).' failing, '.count($warnings).' warnings, '.count($issues)." accessibility issues\n\n"
            .'Signed in as '.static::userLabel($user).".\n".($unchecked ? "\n{$unchecked}\n" : '');

        foreach (['Failing' => $failing, 'Accessibility' => $issues, 'Warnings' => $warnings, 'Skipped' => $skipped, 'Passing' => $passing] as $heading => $lines) {
            if ($lines !== []) {
                $text .= "\n## {$heading}\n\n".implode("\n", $lines)."\n";
            }
        }

        return Response::text($text.($failing === [] && $issues === [] && $warnings === [] ? '' : "\nFix each one, then run storybook-audit again until none remain.\n"));
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

        if (method_exists($page, 'getTable') && $page->getTable()->getColumns() !== []) {
            $table = $page->getTable();
            $visible = collect($table->getColumns())->reject(fn (Column $column): bool => $column->isHidden() || $column->isToggledHiddenByDefault());

            foreach ($visible as $column) {
                if (static::isTitleCase((string) $column->getLabel(), $column->getName())) {
                    $warn("the column \"{$column->getLabel()}\" is in Title Case; write it in sentence case, capitalising only the first word, names and acronyms (Carbon data table).");
                }

                if ($column instanceof TextColumn && $column->isMoney() && ! in_array($column->getAlignment(), [Alignment::End, Alignment::Right], true)) {
                    $warn("the money column `{$column->getName()}` must be right-aligned with `->alignEnd()`, as `tables-money-column` does (USWDS table).");
                }
            }

            $inline = collect($table->getRecordActions())->filter(fn ($action): bool => $action instanceof Action);

            if ($inline->count() > 2) {
                $warn("{$inline->count()} row actions are inline; keep at most two and put the rest in an `ActionGroup` (Carbon data table, NN/g data tables).");
            }

            if (count($table->getFilters()) > 1 && in_array($table->getFiltersLayout(), [FiltersLayout::Dropdown, FiltersLayout::Modal], true)) {
                $warn('several filters are hidden in a dropdown; put them above the table with `layout: FiltersLayout::AboveContentCollapsible` (Carbon filtering).');
            }

            if ($page instanceof ListRecords && blank($table->getEmptyStateDescription()) && $table->getEmptyStateActions() === []) {
                $warn('the empty list gives no next step; add `emptyStateDescription()` and `emptyStateActions()`, as `tables-list-table` does (Carbon empty states).');
            }

            $tableActions = static::flatten([...$table->getHeaderActions(), ...$table->getRecordActions(), ...$table->getToolbarActions()]);
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
            $verbs = $action->shouldOpenModal() ? [$label, trim($action->getModalSubmitActionLabel())] : [$label];

            if ($vague = collect($verbs)->first(fn (string $verb): bool => (bool) preg_match('/^(ok|okay|yes|done|submit|confirm)$/i', $verb))) {
                $warn("`{$action->getName()}` has a button labelled \"{$vague}\"; say what it does with a verb, such as \"Approve order\" (Carbon modal and forms).");
            }

            if (static::isTitleCase($label, $action->getName())) {
                $warn("the action \"{$label}\" is in Title Case; write it in sentence case (Carbon button).");
            }

            $deletes = $action instanceof DeleteAction || $action instanceof ForceDeleteAction || $action instanceof DeleteBulkAction || $action instanceof ForceDeleteBulkAction;
            $removes = $action instanceof DetachAction || $action instanceof DetachBulkAction || $action instanceof DissociateAction || $action instanceof DissociateBulkAction;

            if (($deletes && ! preg_match('/\bdelete\b/i', $label)) || ($removes && preg_match('/\bdelete\b/i', $label))) {
                $warn("`{$action->getName()}` is labelled \"{$label}\", but "
                    .($deletes ? 'it destroys the record, which Carbon calls Delete; keep "Delete"' : 'the record stays, which Carbon calls Remove; keep "Remove"')
                    .' (Carbon common actions).');
            }

            if ($action->isConfirmationRequired() && $action->getModalDescription() === __('filament-actions::modal.confirmation')) {
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

        foreach ($fields as $field) {
            $name = $field->getName();

            if ($field instanceof TextInput && filled($field->getPlaceholder())) {
                $warn("the field `{$name}` uses placeholder(); put the example in helperText(), worded \"For example, …\" (USWDS, GOV.UK).");
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

            if ($field->isLabelHidden()) {
                $warn("the field `{$name}` hides its label; keep it visible, above the field (Carbon forms).");
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

            if ($field instanceof BaseFileUpload && (blank($field->getAcceptedFileTypes()) || $field->getMaxSize() === null)) {
                $warn("the upload `{$name}` does not state its accepted types and largest size; set both and say them in `helperText()`, as `forms-file-upload` does (USWDS file input).");
            }
        }

        $layouts = $page instanceof ListRecords ? [] : rescue(fn (): array => $page->getSchema($page instanceof ViewRecord ? 'infolist' : 'form')?->getFlatComponents(withHidden: true) ?? [], [], report: false);

        foreach ($layouts as $layout) {
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

        foreach ($panel->getNavigation() as $group) {
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
        return str_contains($label, ' ') && $label === Str::headline(Str::afterLast($name, '.'));
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
}
