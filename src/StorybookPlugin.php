<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Pages\Storybook;

class StorybookPlugin implements Plugin
{
    protected bool $houseDefaults = true;

    protected bool $compactTables = false;

    protected bool $compact = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-storybook';
    }

    /** Filament defaults that break a sourced rule (docs/rules.md), corrected app-wide. */
    public function houseDefaults(bool $condition = true): static
    {
        $this->houseDefaults = $condition;

        return $this;
    }

    /**
     * Carbon data table's small rows (32px, the same 14px text), for an app whose screens are data-dense.
     * Off by default: Filament's rows are Carbon's default size, which breaks no rule.
     */
    public function compactTables(bool $condition = true): static
    {
        $this->compactTables = $condition;

        return $this;
    }

    public function hasCompactTables(): bool
    {
        return $this->compactTables || $this->compact;
    }

    /**
     * Carbon's small sizes across the app, for a data-dense app: compact tables, plus 32px menu items and tighter
     * stacks of fields, radios and checkboxes. Off by default, like compactTables().
     */
    public function compact(bool $condition = true): static
    {
        $this->compact = $condition;

        return $this;
    }

    public function hasCompact(): bool
    {
        return $this->compact;
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Storybook::class]);

        $panel->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => collect(['compact-tables' => $this->hasCompactTables(), 'compact' => $this->hasCompact()])
            ->filter()
            ->map(fn (bool $on, string $id): string => '<link rel="stylesheet" href="'.e(FilamentAsset::getStyleHref($id, 'josephdlmd/filament-storybook')).'">')
            ->implode(''));

        if ($this->compact) {
            // The menu is 256px wide (Carbon UI shell left panel); Filament's is 320px. A width the app sets wins.
            if ($panel->getSidebarWidth() === '20rem') {
                $panel->sidebarWidth('16rem');
            }

            // Carbon's modal starts on the left, like every form modal; Filament centres a confirmation round a 48px
            // icon, which compact.css hides. An action that sets its own alignment keeps it.
            Action::configureUsing(fn (Action $action): Action => $action->modalAlignment(Alignment::Start));
        }

        if ($this->houseDefaults) {
            // Lists and record pages use the full width (Carbon 2x grid); Filament stops at 80rem. A width the app sets wins.
            $panel->maxContentWidth($panel->getMaxContentWidth() ?? Width::Full);

            static::applyHouseDefaults();
        }
    }

    public function boot(Panel $panel): void {}

    public static function applyHouseDefaults(): void
    {
        // A delete among other actions is low-emphasis danger (outlined); the confirm button stays solid danger (Carbon button).
        foreach ([DeleteAction::class, ForceDeleteAction::class] as $action) {
            $action::configureUsing(fn (Action $action) => $action->outlined());
        }

        // An error, or a toast with an action, stays until dismissed (Carbon notifications). Decided when it is sent, since
        // ->danger() and ->actions() are chained after make(); a later ->duration() or ->persistent() replaces this.
        Notification::configureUsing(function (Notification $notification): void {
            $timed = $notification->getDuration();
            $notification->duration(fn (): int|string => $notification->getStatus() === 'danger' || $notification->getActions() !== [] ? 'persistent' : $timed);
        });

        // A modal's confirm button repeats the action's verb, never "Submit" or "Confirm"; an "Edit …" form's button saves, so it is
        // "Save" (Carbon modal and forms, action labels). Actions with their own (Create, Save changes, Delete) set it again in setUp().
        Action::configureUsing(fn (Action $action) => $action->modalSubmitActionLabel(fn (Action $action): string => str_starts_with($label = (string) $action->getLabel(), 'Edit ') ? 'Save' : $label));

        // A modal with buttons (a form or a confirmation) closes only by its buttons, × or Esc, so a stray click loses no input;
        // one with nothing to submit, such as View, also closes by clicking outside (Carbon modal: transactional and passive).
        Action::configureUsing(fn (Action $action) => $action->closeModalByClickingAway(fn (Action $action): bool => $action->getModalSubmitAction() === null));

        // An import states its row limit before the upload, beside Filament's example file (USWDS file input: limits up front).
        ImportAction::configureUsing(fn (ImportAction $action) => $action->modalDescription(fn (ImportAction $action): Htmlable => new HtmlString(
            ($action->getMaxRows() ? e('A CSV file of up to '.Number::format($action->getMaxRows()).' rows.').' ' : '').$action->getModalAction('downloadExample')->toHtml(),
        )));

        // The modal a button opens is titled with that button's label: "New supplier", not "Create Supplier" (Carbon modal).
        CreateAction::configureUsing(fn (CreateAction $action) => $action->modalHeading(fn (CreateAction $action): string => (string) $action->getLabel()));

        // A modal with nothing to save closes with its × icon only, not a Close button (Carbon common actions, passive modal).
        ViewAction::configureUsing(fn (ViewAction $action) => $action->modalCancelAction(false));

        // Filament title-cases the model in these headings ("Delete selected Purchase Orders"); Carbon uses sentence case.
        foreach ([
            DeleteBulkAction::class => 'delete.multiple', ForceDeleteBulkAction::class => 'force-delete.multiple', RestoreBulkAction::class => 'restore.multiple',
            DetachBulkAction::class => 'detach.multiple', DissociateBulkAction::class => 'dissociate.multiple', AttachAction::class => 'attach.single',
            AssociateAction::class => 'associate.single', ExportAction::class => 'export', ExportBulkAction::class => 'export', ImportAction::class => 'import',
        ] as $class => $key) {
            $plural = ! str_ends_with($key, '.single');
            $class::configureUsing(fn (Action $action) => $action->modalHeading(fn (Action $action): string => __("filament-actions::{$key}.modal.heading", [
                'label' => $plural ? $action->getPluralModelLabel() : $action->getModelLabel(),
            ])));
        }

        // Carbon's verbs for Filament's: Add an existing record to a relation, Remove it (the record stays), Copy one (Carbon common actions).
        // Translations, so labels, headings, buttons and notifications all change; after boot, once Filament's own lines can load.
        app()->booted(function (): void {
            foreach (['attach' => ['add', 'added'], 'associate' => ['add', 'added'], 'detach' => ['remove', 'removed'], 'dissociate' => ['remove', 'removed'], 'replicate' => ['copy', 'copied']] as $group => [$verb, $done]) {
                $search = [ucfirst($old = rtrim($group, 'e').'ed'), $old, ucfirst($group), $group];
                $lines = Arr::dot((array) trans("filament-actions::{$group}", [], 'en'), "{$group}.");

                app('translator')->addLines(array_map(fn (string $line): string => str_replace($search, [ucfirst($done), $done, ucfirst($verb), $verb], $line), $lines), 'en', 'filament-actions');
            }

            // Import wording: a notification action of one or two words (Carbon notifications); no "please" in an error (GOV.UK error message).
            app('translator')->load('filament-actions', 'import', 'en');
            app('translator')->addLines([
                'import.notifications.completed.actions.download_failed_rows_csv.label' => 'Download errors',
                'import.failure_csv.system_error' => 'A system error stopped this row. Contact support.',
            ], 'en', 'filament-actions');
        });

        // Errors say what to do with the field's own label: "Enter name", not "The name field is required" (GOV.UK error message).
        Field::configureUsing(fn (Field $field) => $field->validationMessages([
            'required' => fn (Field $component): string => static::requiredMessage($component),
        ]));
    }

    public static function requiredMessage(Field $field): string
    {
        $verb = match (true) {
            $field instanceof Select, $field instanceof Radio, $field instanceof CheckboxList,
            $field instanceof ToggleButtons, $field instanceof Checkbox, $field instanceof BaseFileUpload => 'Select',
            default => 'Enter',
        };

        $label = (string) $field->getLabel();

        // Keep acronyms such as "TIN" as written; lower-case an ordinary first word.
        return $verb.' '.(preg_match('/^\p{Lu}{2}/u', $label) ? $label : lcfirst($label));
    }
}
