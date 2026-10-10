<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Josephdlmd\FilamentStorybook\Accessibility;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Livewire\Component;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[IsReadOnly]
class AuditPages extends Tool
{
    protected string $name = 'storybook-audit';

    protected string $description = 'Render the app\'s real resource pages (list, create, edit, view) signed in as a panel user, and report render errors, house-rule breaks and WCAG 2.1 AA issues on each. Run it after building or changing a resource screen. Fix every issue and run it again until none remain.';

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

                    $failing[] = "- `{$key}`: ".class_basename($e).': '.$e->getMessage().' at '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine();
                }
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

        $header = method_exists($page, 'getCachedHeaderActions') ? $page->getCachedHeaderActions() : [];
        $headerActions = static::flatten($header);
        $tableActions = [];

        $primary = collect($header)
            ->filter(fn ($action): bool => $action instanceof Action && $action->isVisible() && in_array($action->getColor() ?? 'primary', ['primary'], true));

        if ($primary->count() > 1) {
            $warn("{$primary->count()} primary actions in the header; keep one primary and make the rest `->color('gray')` or put them in an `ActionGroup` (Carbon button, GOV.UK button).");
        }

        if (method_exists($page, 'getTable') && $page->getTable()->getColumns() !== []) {
            $table = $page->getTable();
            $visible = collect($table->getColumns())->reject(fn (Column $column): bool => $column->isHidden() || $column->isToggledHiddenByDefault());

            foreach ($visible as $column) {
                if ($column instanceof TextColumn && $column->isMoney() && ! in_array($column->getAlignment(), [Alignment::End, Alignment::Right], true)) {
                    $warn("the money column `{$column->getName()}` must be right-aligned with `->alignEnd()`, as `tables-money-column` does (GOV.UK and USWDS tables).");
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

            $tableActions = static::flatten([...$table->getRecordActions(), ...$table->getToolbarActions()]);
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

        // A list page reuses the resource form for its create modal; that form is audited on the create page.
        $fields = $page instanceof ListRecords ? [] : rescue(fn (): array => $page->form->getFlatFields(withHidden: false), [], report: false);

        foreach ($fields as $field) {
            $name = $field->getName();

            if ($field instanceof TextInput && filled($field->getPlaceholder())) {
                $warn("the field `{$name}` uses placeholder(); put the example in helperText(), worded \"For example, …\" (USWDS, GOV.UK).");
            }

            if ($field->isLabelHidden()) {
                $warn("the field `{$name}` hides its label; keep it visible, above the field (W3C WAI, GOV.UK).");
            }

            if ($field instanceof Toggle) {
                $warn("the field `{$name}` is a toggle in a form that is saved; use a `Checkbox`, as `forms-yes-or-no` does (Carbon toggle and checkbox).");
            }

            if ($field instanceof DateTimePicker && ! $field->isNative()) {
                $warn("the date field `{$name}` uses `native(false)`, which cannot be typed into; use the native picker, as `forms-date` does (USWDS date picker, GOV.UK dates).");
            }

            if ($field instanceof BaseFileUpload && (blank($field->getAcceptedFileTypes()) || $field->getMaxSize() === null)) {
                $warn("the upload `{$name}` does not state its accepted types and largest size; set both and say them in `helperText()`, as `forms-file-upload` does (USWDS file input).");
            }
        }

        foreach (static::errorMessages($page) as $message) {
            $warn("the error \"{$message}\" doesn't say what to do; word it like \"Enter name\" or \"Select status\" (GOV.UK error message, NN/g 2023).");
        }

        return $warnings;
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
