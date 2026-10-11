<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
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
     * Carbon data table's medium rows (40px, the same 14px text) with the toolbar and pagination as tall, for an app
     * whose screens are data-dense. Off by default: Filament's rows break no rule.
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

            // A menu opens 4px from its button (Carbon popover).
            ActionGroup::configureUsing(fn (ActionGroup $group): ActionGroup => $group->dropdownOffset(4));
        }

        if ($this->houseDefaults) {
            // Leaving a form with unsaved changes asks first (Carbon global header: "If state or progress will be lost, inform users").
            if (! $panel->hasUnsavedChangesAlerts()) {
                $panel->unsavedChangesAlerts();
            }

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

        // In a modal or slide-over, Cancel is on the left and the primary button on the right (Carbon modal and dialogs). Filament
        // puts them in that order, in the page as on screen, only when the footer is centred. A full-page form keeps its primary first.
        Action::configureUsing(fn (Action $action) => $action->modalFooterActionsAlignment(Alignment::Center));

        // A confirmation that destroys nothing focuses its primary button; a danger one keeps Filament's focus on Cancel (Carbon
        // modal: "the primary button takes focus"; "For destructive interactions, the 'cancel' button takes focus").
        // Filament passes the submit button to the closure as `$action`, by name.
        Action::configureUsing(fn (Action $parent) => $parent->modalSubmitAction(fn (Action $action): Action => $parent->isConfirmationRequired() && $parent->getColor() !== 'danger'
            ? $action->extraAttributes(['autofocus' => true], merge: true)
            : $action));

        // A form modal keeps its header and buttons in view while the fields scroll (Carbon modal). Confirmations are short.
        Action::configureUsing(fn (Action $action) => $action
            ->stickyModalHeader(fn (Action $action): bool => ! $action->isConfirmationRequired())
            ->stickyModalFooter(fn (Action $action): bool => ! $action->isConfirmationRequired()));

        // An icon-only menu ("…") says what it holds in a tooltip, on hover and focus; a labelled one ends with a chevron
        // (Carbon tooltip: "Every icon-only button needs a tooltip"; Carbon menu buttons: "always be a caret").
        ActionGroup::configureUsing(fn (ActionGroup $group) => $group
            ->tooltip(fn (ActionGroup $group): ?string => $group->isIconButton() ? (string) $group->getLabel() : null)
            ->icon(fn (ActionGroup $group): ?Heroicon => $group->isButton() ? Heroicon::ChevronDown : null)
            ->iconPosition(fn (ActionGroup $group): IconPosition => $group->isButton() ? IconPosition::After : IconPosition::Before));

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

            // Carbon's words for signing in and out: "Log in", "Log out" (Carbon login pattern; owner's decision, 2026-10-11).
            foreach (['auth/pages/login', 'auth/pages/register', 'auth/pages/password-reset/request-password-reset', 'layout', 'widgets/account-widget'] as $group) {
                $lines = Arr::dot((array) trans("filament-panels::{$group}", [], 'en'), "{$group}.");
                $lines = array_map(fn (mixed $line): mixed => is_string($line) ? str_replace(['Sign in', 'sign in', 'Sign out', 'back to login', 'Login'], ['Log in', 'log in', 'Log out', 'back to log in', 'Log in'], $line) : $line, $lines);

                app('translator')->addLines(array_filter($lines, 'is_string'), 'en', 'filament-panels');
            }

            // A failed log in says what happened and what to do (Carbon login: "Incorrect IBMid or password. Try again.").
            app('translator')->addLines(['auth/pages/login.messages.failed' => 'Incorrect email or password. Try again.'], 'en', 'filament-panels');

            // Import wording: a notification action of one or two words (Carbon notifications); no "please" in an error (GOV.UK error message).
            app('translator')->load('filament-actions', 'import', 'en');
            app('translator')->addLines([
                'import.notifications.completed.actions.download_failed_rows_csv.label' => 'Download errors',
                'import.failure_csv.system_error' => 'A system error stopped this row. Contact support.',
            ], 'en', 'filament-actions');
        });

        // The browser's own validation is off, so a form's errors are ours, worded as GOV.UK asks, instead of "Please fill out
        // this field." (GOV.UK validation: "Turn off HTML5 validation"). A page form and a modal window are each a <form>.
        Form::configureUsing(fn (Form $form) => $form->extraAttributes(['novalidate' => true], merge: true));
        Action::configureUsing(fn (Action $action) => $action->extraModalWindowAttributes(['novalidate' => true], merge: true));

        // An amount or quantity is a text input with a numeric keypad, not type="number", which rounds, steps and silently refuses
        // what is typed (Carbon number input: avoid it "for continuous variables such as prices"; GOV.UK text input).
        TextInput::configureUsing(fn (TextInput $input) => $input->type(fn (TextInput $input): ?string => $input->isNumeric() ? 'text' : null));

        // One convention throughout: optional fields say "(optional)", required ones carry no asterisk (Carbon form; owner, 2026-10-11).
        // A required input still has the required attribute, and a required radio group aria-required, for screen readers.
        Field::configureUsing(fn (Field $field) => $field->markAsRequired(false));

        // One column (Carbon forms: "Carbon generally recommends single-column forms"): Filament gives a resource's form and
        // view two columns unless the schema sets its own. Only top-level schemas change; a section, grid or fieldset keeps
        // its own columns, and a form that sets ->columns(2) gets them (and a storybook-audit warning).
        Schema::configureUsing(fn (Schema $schema) => $schema->columns(1));

        // A table shows a skeleton while it reloads, not a spinner (Carbon data table: "use skeleton states instead of spinners").
        // Filament added the setting in a later 5.x; on an older one, tables keep the spinner.
        if (method_exists(Table::class, 'loadingSkeleton')) {
            Table::configureUsing(fn (Table $table) => $table->loadingSkeleton());
        }

        // A toggle in a table shows a tick when on, so on and off don't differ by colour alone (Carbon toggle; WCAG 1.4.1).
        ToggleColumn::configureUsing(fn (ToggleColumn $column) => $column->onIcon(Heroicon::Check));

        // An upload's drop zone in sentence case, saying both ways to add a file, instead of FilePond's "Drag & Drop your
        // files or Browse" (Carbon file uploader, archived: "drag and drop a file into the drop zone or click on the button").
        FileUpload::configureUsing(fn (FileUpload $upload) => $upload->placeholder(fn (FileUpload $upload): string => $upload->isMultiple()
            ? 'Drag and drop files here or <span class="filepond--label-action">click to upload</span>'
            : 'Drag and drop a file here or <span class="filepond--label-action">click to upload</span>'));

        // A yes-or-no filter's empty choice says "All", as a select filter's does, not Filament's "-" (plain words, no codes; Carbon empty states).
        TernaryFilter::configureUsing(fn (TernaryFilter $filter) => $filter->placeholder(__('filament-tables::table.filters.select.placeholder')));

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
