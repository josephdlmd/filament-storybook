<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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
                    $rendered[$key] = $page->html();
                    $warnings = [...$warnings, ...static::warnings($key, $page->instance())];
                    $passing[] = "- `{$key}`";
                } catch (HttpException $e) {
                    $skipped[] = "- `{$key}`: ".static::userLabel($user)." may not open it ({$e->getStatusCode()}); pass \"as\" with a user who can.";
                } catch (Throwable $e) {
                    while ($e->getPrevious() !== null) {
                        $e = $e->getPrevious();
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

        if (method_exists($page, 'getTable') && $page->getTable()->getColumns() !== []) {
            $table = $page->getTable();
            $visible = collect($table->getColumns())->reject(fn (Column $column): bool => $column->isHidden() || $column->isToggledHiddenByDefault());

            if ($visible->count() > 8) {
                $warnings[] = "- `{$key}`: {$visible->count()} columns are visible; show at most 8 and make the rest `->toggleable(isToggledHiddenByDefault: true)` (house choice).";
            }

            if ($table->isPaginated() && (int) $table->getDefaultPaginationPageOption() !== 25) {
                $warnings[] = "- `{$key}`: show 25 rows per page with `->defaultPaginationPageOption(25)`, as `tables-list-table` does (house choice).";
            }

            foreach ($visible as $column) {
                if ($column instanceof TextColumn && $column->isMoney() && ! in_array($column->getAlignment(), [Alignment::End, Alignment::Right], true)) {
                    $warnings[] = "- `{$key}`: the money column `{$column->getName()}` must be right-aligned with `->alignEnd()`, as `tables-money-column` does (GOV.UK, Microsoft).";
                }
            }
        }

        // A list page reuses the resource form for its create modal; that form is audited on the create page.
        $fields = $page instanceof ListRecords ? [] : rescue(fn (): array => $page->form->getFlatFields(withHidden: false), [], report: false);

        foreach ($fields as $field) {
            if ($field instanceof TextInput && filled($field->getPlaceholder())) {
                $warnings[] = "- `{$key}`: the field `{$field->getName()}` puts an example in placeholder(); use mask() for a fixed shape, otherwise a one-line helperText() (GOV.UK, NN/g).";
            }

            if ($field->isLabelHidden()) {
                $warnings[] = "- `{$key}`: the field `{$field->getName()}` hides its label; keep it visible (GOV.UK, NN/g).";
            }
        }

        return $warnings;
    }
}
