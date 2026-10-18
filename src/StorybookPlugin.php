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
use Filament\Auth\Pages\EditProfile;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Auth\Pages\Register;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Pages\Enums\SubNavigationPosition;
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
use Illuminate\Validation\Rules\Password;
use Josephdlmd\FilamentStorybook\GlobalSearch\SentenceCaseGlobalSearchProvider;
use Josephdlmd\FilamentStorybook\Notifications\SignInMethodChanged;
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
     * Carbon data table's medium rows (40px, the same 14px text) and its small toolbar and pagination, for an app
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

            // A page's own navigation, such as a cluster's settings pages, is tabs at the top (Carbon UI shell left panel: "use tabs
            // within the page"); Filament puts it beside the page. Filament's getter never says whether the app set one, so ask its property.
            if ((fn (): mixed => $this->subNavigationPosition)->call($panel) === null) {
                $panel->subNavigationPosition(SubNavigationPosition::Top);
            }

            // An idle session warns 2 minutes before it ends, with a button to stay logged in (WCAG 2.2.1: warned, "at least 20
            // seconds to extend"; NIST SP 800-63B-4: prompt "two minutes before"). Lifetimes over 20 hours are exempt.
            $panel->renderHook(PanelsRenderHook::BODY_END, fn (): string => filament()->auth()->check() && config('session.lifetime') * 60 <= 20 * 3600
                ? view('filament-storybook::session-warning', ['lifetime' => config('session.lifetime') * 60, 'warning' => 120])->render()
                : '');

            static::applyHouseDefaults();
        }
    }

    public function boot(Panel $panel): void {}

    public static function applyHouseDefaults(): void
    {
        // Global search heads each resource's group in sentence case, as HasSentenceCaseLabels leaves the plural model label
        // lower case; a panel's own provider is untouched (Carbon content).
        app()->bind(DefaultGlobalSearchProvider::class, SentenceCaseGlobalSearchProvider::class);

        // Global search's placeholder says what it searches, "Search customers and sales invoices", from the resources the user may
        // search in this panel (Carbon search: placeholder text "hinting at what the user can search for").
        Filament::serving(function (): void {
            $labels = collect(Filament::getResources())->filter(fn (string $resource): bool => $resource::canGloballySearch())->map(fn (string $resource): string => $resource::getPluralModelLabel());

            if ($labels->isNotEmpty()) {
                app('translator')->load('filament-panels', 'global-search', 'en');
                app('translator')->addLines(['global-search.field.placeholder' => 'Search '.$labels->join(', ', ' and ')], 'en', 'filament-panels');
            }
        });

        // A delete among other actions is low-emphasis danger (outlined); the confirm button stays solid danger (Carbon button).
        foreach ([DeleteAction::class, ForceDeleteAction::class, DeleteBulkAction::class, ForceDeleteBulkAction::class] as $action) {
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
                $lines = array_map(fn (mixed $line): mixed => is_string($line) ? str_replace(['Sign in', 'sign in', 'signing in', 'Sign out', 'back to login', 'Login'], ['Log in', 'log in', 'logging in', 'Log out', 'back to log in', 'Log in'], $line) : $line, $lines);

                app('translator')->addLines(array_filter($lines, 'is_string'), 'en', 'filament-panels');
            }

            // A row's checkbox says what it selects, in plain words; its checked state is announced already (W3C WAI-ARIA APG
            // checkbox; WCAG 2.4.6). The house script puts the row's first value in place of the record's id ("Select SUP-0001").
            app('translator')->load('filament-tables', 'table', 'en');
            app('translator')->addLines([
                'table.fields.bulk_select_page.label' => 'Select all on this page',
                'table.fields.bulk_select_record.label' => 'Select :key',
                'table.fields.bulk_select_group.label' => 'Select group :title',
            ], 'en', 'filament-tables');

            // Two-factor wording to the house rules: labels say what goes in the field, errors say what to do with no "invalid"
            // or "please", a confirmation says the consequence, a dialog has no links that leave it, and it is "log in"
            // (Carbon forms, modal, dialogs and login; GOV.UK error messages and confirm a phone number: "Enter a correct
            // security code"; IBM Accessibility content design).
            $code = [
                'label' => 'Security code',
                'validation_attribute' => 'security code',
                'below_content' => 'The 6-digit code in your authenticator app',
                'messages.invalid' => 'Enter the code your authenticator app shows now',
                'messages.rate_limited' => 'Too many attempts. Try again in a few minutes',
            ];
            $recoveryCode = [
                'label' => 'Recovery code',
                'messages.invalid' => 'Enter a recovery code you haven\'t used before',
                'messages.rate_limited' => 'Too many attempts. Try again in a few minutes',
            ];
            $saveCodes = 'Save these recovery codes somewhere safe. They are shown only once. If you lose your authenticator app, each code lets you log in once.';
            $prefix = fn (string $key, array $lines): array => collect($lines)->mapWithKeys(fn (string $line, string $name): array => ["{$key}.{$name}" => $line])->all();

            foreach ([
                'auth/multi-factor/app/actions/set-up' => [
                    'modal.description' => 'You need an authenticator app on your phone, such as Google Authenticator or Microsoft Authenticator.',
                    'modal.content.qr_code.instruction' => 'Scan this QR code with your authenticator app',
                    'modal.content.text_code.instruction' => 'Or type this key into the app',
                    'modal.content.recovery_codes.instruction' => $saveCodes,
                    'modal.actions.submit.label' => 'Turn on',
                    'notifications.enabled.title' => 'Authenticator app turned on',
                    ...$prefix('modal.form.code', $code),
                ],
                'auth/multi-factor/app/actions/disable' => [
                    'modal.heading' => 'Turn off authenticator app',
                    'modal.description' => 'You will log in with your password alone, which is less secure.',
                    'modal.actions.submit.label' => 'Turn off',
                    'notifications.disabled.title' => 'Authenticator app turned off',
                    ...$prefix('modal.form.code', [...$code, 'actions.use_recovery_code.label' => 'Use a recovery code instead']),
                    ...$prefix('modal.form.recovery_code', $recoveryCode),
                ],
                'auth/multi-factor/app/actions/regenerate-recovery-codes' => [
                    'label' => 'Make new recovery codes',
                    'modal.heading' => 'Make new recovery codes',
                    'modal.description' => 'Your old recovery codes stop working at once.',
                    'modal.form.password.label' => 'Current password',
                    'modal.actions.submit.label' => 'Make new codes',
                    'notifications.regenerated.title' => 'New recovery codes made',
                    'show_new_recovery_codes.modal.description' => $saveCodes,
                    ...$prefix('modal.form.code', $code),
                ],
                'auth/multi-factor/app/provider' => [
                    'management_schema.actions.below_content' => 'A 6-digit code from an app on your phone, asked for each time you log in',
                    ...$prefix('login_form.code', [...$code, 'actions.use_recovery_code.label' => 'Use a recovery code instead']),
                    ...$prefix('login_form.recovery_code', $recoveryCode),
                ],
                'auth/multi-factor/recovery-codes-modal-content' => ['actions.0' => '', 'actions.copy.label' => 'Copy', 'actions.2' => 'all the codes.'],
                'auth/multi-factor/pages/set-up-required-multi-factor-authentication' => ['subheading' => 'Each time you log in, you\'ll enter a code from an app on your phone as well as your password.'],
            ] as $group => $lines) {
                app('translator')->load('filament-panels', $group, 'en');
                app('translator')->addLines($prefix($group, $lines), 'en', 'filament-panels');
            }

            // The empty notifications panel says what will appear in it, not "Please check again later" (Carbon empty states).
            app('translator')->load('filament-notifications', 'database', 'en');
            app('translator')->addLines(['database.modal.empty.description' => 'Messages about work that finished and records that need you appear here.'], 'en', 'filament-notifications');

            // Global search with no results says what to try next; Filament matches every word, so fewer words find more (Carbon search: "suggest a follow-up action").
            app('translator')->load('filament-panels', 'global-search', 'en');
            app('translator')->addLines(['global-search.no_results_message' => 'No results. Check the spelling, or search for fewer words.'], 'en', 'filament-panels');

            // A failed log in says what happened and what to do (Carbon login: "Incorrect IBMid or password. Try again.").
            app('translator')->addLines(['auth/pages/login.messages.failed' => 'Incorrect email or password. Try again.'], 'en', 'filament-panels');

            // Import wording: a notification action of one or two words (Carbon notifications); no "please" in an error (GOV.UK error message).
            app('translator')->load('filament-actions', 'import', 'en');
            app('translator')->addLines([
                'import.notifications.completed.actions.download_failed_rows_csv.label' => 'Download errors',
                'import.failure_csv.system_error' => 'A system error stopped this row. Contact support.',
            ], 'en', 'filament-actions');

            // GOV.UK's error templates (text input, date input). Loaded first, so the app's own lang file can't replace them later.
            app('translator')->load('*', 'validation', 'en');
            app('translator')->addLines(static::ERROR_MESSAGES, 'en');
        });

        // A new password: 15 characters at least, or 8 when every user must also use two-factor; any characters, no rules on
        // their mix; never a password known from a breach (NIST SP 800-63B-4). The breach check sends Have I Been Pwned the
        // first 5 characters of the password's hash, never the password; if the service can't be reached, the password passes.
        Password::defaults(fn (): Password => Password::min(static::minimumPasswordLength())->uncompromised());

        // A new password's helper text states its minimum length (Carbon login, text input).
        TextInput::configureUsing(fn (TextInput $input) => $input->helperText(function (TextInput $component): ?string {
            $page = $component->hasContainer() ? $component->getLivewire() : null;

            return $component->getName() === 'password' && Arr::first([Register::class, ResetPassword::class, EditProfile::class], fn (string $class): bool => $page instanceof $class)
                ? 'At least '.static::minimumPasswordLength().' characters'
                : null;
        }));

        // The account's owner hears by email when two-factor is turned on or off, in case it wasn't them (NIST SP 800-63B-4).
        // Filament builds both with Action::make(), so they are found by their names, which are set before this runs.
        Action::configureUsing(function (Action $action): void {
            $change = ['setUpAppAuthentication' => 'turned on', 'disableAppAuthentication' => 'turned off'][$action->getName()] ?? null;

            if ($change) {
                // A user model without Laravel's Notifiable trait can't be emailed; the change still goes through. So does a mail
                // server that is down: the error is logged, not shown as if the change had failed.
                $action->after(fn () => method_exists($user = auth()->user(), 'notify') ? rescue(fn () => $user->notify(new SignInMethodChanged('two-factor authentication with an authenticator app', $change))) : null);
            }
        });

        // The browser's own validation is off, so a form's errors are ours, worded as GOV.UK asks, instead of "Please fill out
        // this field." (GOV.UK validation: "Turn off HTML5 validation"). A page form and a modal window are each a <form>.
        Form::configureUsing(fn (Form $form) => $form->extraAttributes(['novalidate' => true], merge: true));
        Action::configureUsing(fn (Action $action) => $action->extraModalWindowAttributes(['novalidate' => true], merge: true));

        // An amount or quantity is a text input with a numeric keypad, not type="number", which rounds, steps and silently refuses
        // what is typed (Carbon number input: avoid it "for continuous variables such as prices"; GOV.UK text input).
        TextInput::configureUsing(fn (TextInput $input) => $input->type(fn (TextInput $input): ?string => $input->isNumeric() ? 'text' : null));

        // Spaces before or after an answer are ignored, as Livewire skips Laravel's TrimStrings: a pasted " 1250.50" would fail
        // the amount's check (GOV.UK validation: ignore unwanted characters "before or after an answer"). Passwords stay as typed.
        TextInput::configureUsing(fn (TextInput $input) => $input->trim(fn (TextInput $input): bool => ! $input->isPassword()));
        Textarea::configureUsing(fn (Textarea $textarea) => $textarea->trim());

        // Fields about the user say what they hold, so the browser can fill them in (WCAG 1.3.5): Filament's log in gives the
        // email "on", and its sign-up and profile pages give the name and email nothing. Only on those pages: a customer's
        // email elsewhere is not the user's. Extra attributes win over Filament's own value.
        TextInput::configureUsing(fn (TextInput $input) => $input->extraInputAttributes(function (TextInput $component): array {
            $page = $component->hasContainer() ? $component->getLivewire() : null;

            if (! Arr::first([Login::class, RequestPasswordReset::class, ResetPassword::class, Register::class, EditProfile::class], fn (string $class): bool => $page instanceof $class)) {
                return [];
            }

            return match ($component->getName()) {
                'email' => ['autocomplete' => $page instanceof Login || $page instanceof RequestPasswordReset ? 'username' : 'email'],
                'name' => ['autocomplete' => 'name'],
                default => [],
            };
        }, merge: true));

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

        // Applied filters, the search and the sort stay for the session (Carbon search: a filter "should persist during future
        // searches until the user either discards the filter or begins a new session"). A table that sets its own keeps it.
        Table::configureUsing(fn (Table $table) => $table->persistFiltersInSession()->persistSearchInSession()->persistSortInSession());

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
        // A field names itself in every error as its label is written, so "TIN" stays "TIN" where Filament would write "tIN".
        Field::configureUsing(fn (Field $field) => $field
            ->validationAttribute(fn (Field $component): string => static::errorName($component))
            ->validationMessages([
                'required' => fn (Field $component): string => static::requiredMessage($component),
            ]));
    }

    /**
     * GOV.UK's error templates for the rules Laravel words its own way ("The amount field must be a number."). Laravel's
     * lines, not each field's messages, so a field that sets a message of its own keeps these for its other rules.
     *
     * @var array<string, string>
     */
    public const ERROR_MESSAGES = [
        'validation.required' => 'Enter :attribute',
        'validation.numeric' => ':Attribute must be a number',
        'validation.integer' => ':Attribute must be a whole number',
        'validation.min.numeric' => ':Attribute must be :min or more',
        'validation.max.numeric' => ':Attribute must be :max or less',
        'validation.between.numeric' => ':Attribute must be between :min and :max',
        'validation.min.string' => ':Attribute must be :min characters or more',
        'validation.password.uncompromised' => 'Choose a different password: this one has appeared in a data breach',
        'validation.max.string' => ':Attribute must be :max characters or less',
        'validation.between.string' => ':Attribute must be between :min and :max characters',
        'validation.date' => ':Attribute must be a real date',
        'validation.after' => ':Attribute must be after :date',
        'validation.after_or_equal' => ':Attribute must be the same as or after :date',
        'validation.before' => ':Attribute must be before :date',
        'validation.before_or_equal' => ':Attribute must be the same as or before :date',
        'validation.regex' => 'Enter :attribute in the correct format',
        'validation.email' => 'Enter :attribute in the correct format, like name@example.com',
    ];

    public static function errorName(Field $field): string
    {
        $label = preg_replace('/\s*\(optional\)$/', '', (string) $field->getLabel());

        // Keep acronyms such as "TIN" as written; lower-case an ordinary first word. "(optional)" is for the label only.
        return preg_match('/^\p{Lu}{2}/u', $label) ? $label : lcfirst($label);
    }

    /** NIST SP 800-63B-4: 15 characters when a password is the only factor, 8 when another is always required. */
    public static function minimumPasswordLength(): int
    {
        return Filament::getCurrentPanel()?->isMultiFactorAuthenticationRequired() ? 8 : 15;
    }

    public static function requiredMessage(Field $field): string
    {
        $verb = match (true) {
            $field instanceof Select, $field instanceof Radio, $field instanceof CheckboxList,
            $field instanceof ToggleButtons, $field instanceof Checkbox, $field instanceof BaseFileUpload => 'Select',
            default => 'Enter',
        };

        return $verb.' '.static::errorName($field);
    }
}
