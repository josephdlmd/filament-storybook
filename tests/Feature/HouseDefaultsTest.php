<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;

it('loads the house CSS and script the patterns depend on, on every panel page', function (): void {
    expect(collect(Filament\Support\Facades\FilamentAsset::getStyles(['josephdlmd/filament-storybook']))->reject->isLoadedOnRequest()->map->getId()->values()->all())->toBe(['house'])
        ->and(collect(Filament\Support\Facades\FilamentAsset::getScripts(['josephdlmd/filament-storybook']))->map->getId()->values()->all())->toBe(['house']);

    // Copyable values become buttons the keyboard can use, and copying is announced (WCAG 2.1.1, 4.1.3).
    expect(file_get_contents(__DIR__.'/../../resources/js/house.js'))
        ->toContain("setAttribute('role', 'button')")
        ->toContain("['Enter', ' ']")
        ->toContain('Copied to clipboard')
        ->toContain('.fi-ta-actions :is(a, button):not([aria-label])')
        ->toContain(':is(.fi-ta-text-input, .fi-ta-select, .fi-ta-checkbox, .fi-ta-toggle)')
        // Top navigation's groups are list items of its list, not dropdowns loose in it (WCAG 1.3.1).
        ->toContain(".fi-topbar-nav-groups > :not(li, [role=\"listitem\"])")
        // Focus returns to the button a modal came from, or the same action on the row in its place (WCAG 2.4.3).
        ->toContain("action.textContent.trim() === last.label")
        // Chart axes take the theme's text colour in light and dark mode (WCAG 1.4.3).
        ->toContain('window.filamentChartJsGlobalPlugins');
});

it('warns 2 minutes before an idle session ends, with a button to stay logged in, unless it lasts over 20 hours', function (): void {
    config(['session.lifetime' => 120]);

    $this->get('/fixture/storybook?component=forms-money-input')
        ->assertSee('data-fs-session-lifetime="7200" data-fs-session-warning="120"', false)
        ->assertSee('You&#039;ll be logged out in 2 minutes', false)
        ->assertSee('Stay logged in');

    expect(file_get_contents(__DIR__.'/../../resources/js/house.js'))->toContain("new CustomEvent('open-modal', { detail: { id } })");

    config(['session.lifetime' => 21 * 60]);

    $this->get('/fixture/storybook?component=forms-money-input')->assertDontSee('Stay logged in');
});

it('makes tables, or the whole app, compact with Carbon\'s small sizes, only on a panel that asks for it', function (): void {
    $page = fn () => $this->get('/fixture/storybook?component=tables-list-table')->assertOk();

    $page()->assertDontSee('compact-tables.css', false);

    Filament\Facades\Filament::getPanel('fixture')->getPlugin('filament-storybook')->compactTables();

    $page()->assertSee('compact-tables.css', false)->assertDontSee('compact.css', false);

    Filament\Facades\Filament::getPanel('fixture')->getPlugin('filament-storybook')->compactTables(false)->compact();

    // compact() brings the compact tables with it.
    $page()->assertSee('compact-tables.css', false)->assertSee('compact.css', false);

    // Rows of 40px and tighter stacks still leave every checkbox, radio and action its WCAG 2.2 target size.
    expect(testStoriesWithTheme(['ids' => ['tables-list-table', 'records-related-records', 'tables-money-column', 'tables-status-badge', 'forms-choice', 'forms-record-form', 'forms-yes-or-no']]))
        ->toContain('0 failing, 0 warnings, 0 accessibility issues');
});

it('starts confirmations on the left and narrows the menu under compact(), as in Carbon', function (): void {
    expect(Filament\Actions\DeleteAction::make()->getModalAlignment())->toBe(Filament\Support\Enums\Alignment::Center);

    Josephdlmd\FilamentStorybook\StorybookPlugin::make()->compact()->register($panel = Filament\Panel::make()->id('compact'));
    $delete = Filament\Actions\DeleteAction::make();

    expect($delete->getModalAlignment())->toBe(Filament\Support\Enums\Alignment::Start)
        ->and($panel->getSidebarWidth())->toBe('16rem');
});

it('keeps the compact and house selectors on classes Filament still uses, so an upgrade can\'t undo them silently', function (): void {
    preg_match_all('/\.(fi-[\w-]+)/', file_get_contents(__DIR__.'/../../resources/css/compact-tables.css').file_get_contents(__DIR__.'/../../resources/css/compact.css').file_get_contents(__DIR__.'/../../resources/css/house.css'), $classes);
    // A page's own class (fi-resource-view-record-page) is set in PHP and styled by no theme rule.
    $theme = file_get_contents(__DIR__.'/../../vendor/filament/filament/dist/theme.css').file_get_contents(__DIR__.'/../../vendor/filament/filament/src/Resources/Pages/ViewRecord.php');

    foreach (array_unique($classes[1]) as $class) {
        expect(preg_match('/[.\']'.preg_quote($class, '/').'(?![\w-])/', $theme))->toBe(1, "Filament no longer styles .{$class}");
    }
});

it('puts a page\'s own navigation in tabs at the top, unless the app sets its own place', function (): void {
    expect(Filament\Facades\Filament::getPanel('fixture')->getSubNavigationPosition())->toBe(Filament\Pages\Enums\SubNavigationPosition::Top);

    $panel = Filament\Panel::make()->id('side')->subNavigationPosition(Filament\Pages\Enums\SubNavigationPosition::End);
    Josephdlmd\FilamentStorybook\StorybookPlugin::make()->register($panel);

    expect($panel->getSubNavigationPosition())->toBe(Filament\Pages\Enums\SubNavigationPosition::End);
});

it('opens a row\'s own menu on a right-click, unless the app turns it off', function (): void {
    // What the plugin's hooks put at the start of the page's body, read from the panel it registered on.
    $render = function (Josephdlmd\FilamentStorybook\StorybookPlugin $plugin): string {
        $plugin->register($panel = Filament\Panel::make()->id('menus'));

        return collect((fn (): array => $this->renderHooks)->call($panel)[Filament\View\PanelsRenderHook::BODY_START] ?? [])->flatten()->map(fn (Closure $hook): string => (string) $hook())->implode('');
    };

    expect($render(Josephdlmd\FilamentStorybook\StorybookPlugin::make()))->not->toContain('fsRowContextMenus')
        ->and($render(Josephdlmd\FilamentStorybook\StorybookPlugin::make()->rowContextMenus(false)))->toContain('window.fsRowContextMenus = false')
        ->and(file_get_contents(__DIR__.'/../../resources/js/house.js'))->toContain("document.addEventListener('contextmenu'");
});

it('gives every page the full width, unless the app sets its own', function (): void {
    expect(Filament\Facades\Filament::getPanel('fixture')->getMaxContentWidth())->toBe(Filament\Support\Enums\Width::Full);

    $panel = Filament\Panel::make()->id('narrow')->maxContentWidth(Filament\Support\Enums\Width::SevenExtraLarge);
    Josephdlmd\FilamentStorybook\StorybookPlugin::make()->register($panel);

    expect($panel->getMaxContentWidth())->toBe(Filament\Support\Enums\Width::SevenExtraLarge);
});

it('puts Cancel left of the primary in every modal, and keeps form modals\' header and buttons in view', function (): void {
    $edit = Filament\Actions\Action::make('changeDueDate')->schema([]);
    $delete = Filament\Actions\DeleteAction::make();

    expect($edit->getModalFooterActionsAlignment())->toBe(Filament\Support\Enums\Alignment::Center)
        ->and(array_keys($edit->getModalFooterActions()))->toBe(['cancel', 'submit'])
        ->and($edit->isModalHeaderSticky())->toBeTrue()
        ->and($edit->isModalFooterSticky())->toBeTrue()
        ->and($delete->isModalHeaderSticky())->toBeFalse();
});

it('names an icon-only menu in a tooltip and ends a labelled one with a chevron', function (): void {
    $overflow = Filament\Actions\ActionGroup::make([Filament\Actions\Action::make('archive')]);
    $more = Filament\Actions\ActionGroup::make([Filament\Actions\Action::make('archive')])->label('More')->button();

    expect($overflow->getTooltip())->toBe('Actions')
        ->and($more->getTooltip())->toBeNull()
        ->and($more->getIcon())->toBe(Filament\Support\Icons\Heroicon::ChevronDown)
        ->and($more->getIconPosition())->toBe(Filament\Support\Enums\IconPosition::After)
        ->and($overflow->getIcon())->toBe(Filament\Support\Icons\Heroicon::EllipsisVertical);
});

it('leaves form errors to the app, keeps amounts as text and marks optional fields, not required ones', function (): void {
    $form = Filament\Schemas\Components\Form::make([]);
    $modal = Filament\Actions\Action::make('changeDueDate');
    $amount = Filament\Forms\Components\TextInput::make('amount')->numeric();

    expect($form->getExtraAttributes())->toHaveKey('novalidate')
        ->and($modal->getExtraModalWindowAttributes())->toHaveKey('novalidate')
        ->and($amount->getType())->toBe('text')
        ->and($amount->getInputMode())->toBe('decimal')
        ->and($amount->isTrimmed())->toBeTrue()
        ->and(Filament\Forms\Components\TextInput::make('password')->password()->isTrimmed())->toBeFalse()
        ->and(Filament\Forms\Components\Textarea::make('note')->isTrimmed())->toBeTrue()
        ->and(Filament\Forms\Components\TextInput::make('email')->email()->getType())->toBe('email')
        ->and(Filament\Forms\Components\TextInput::make('name')->required()->isMarkedAsRequired())->toBeFalse()
        ->and(Filament\Tables\Columns\ToggleColumn::make('is_active')->getOnIcon())->toBe(Filament\Support\Icons\Heroicon::Check)
        ->and(Filament\Tables\Filters\TernaryFilter::make('has_credit_limit')->getPlaceholder())->toBe('All')
        ->and(Filament\Forms\Components\FileUpload::make('file')->getPlaceholder())->toStartWith('Drag and drop a file here or')
        ->and(Filament\Forms\Components\FileUpload::make('files')->multiple()->getPlaceholder())->toStartWith('Drag and drop files here or')
        ->and(Filament\Schemas\Schema::make()->getColumns('lg'))->toBe(1)
        ->and(Filament\Schemas\Schema::make()->hasCustomColumns())->toBeTrue()
        ->and(Filament\Schemas\Schema::make()->columns(2)->getColumns('lg'))->toBe(2)
        ->and(Filament\Facades\Filament::getPanel('fixture')->hasUnsavedChangesAlerts())->toBeTrue()
        ->and(__('filament-panels::auth/pages/login.messages.failed'))->toBe('Incorrect email or password. Try again.');
});

it('focuses the primary in a confirmation that destroys nothing, and Cancel in a danger one', function (): void {
    $reminder = Filament\Actions\Action::make('sendReminder')->requiresConfirmation();

    expect($reminder->getModalSubmitAction()->getExtraAttributes())->toHaveKey('autofocus')
        ->and(Filament\Actions\DeleteAction::make()->getModalSubmitAction()->getExtraAttributes())->not->toHaveKey('autofocus');
});

it('shows a skeleton while a table reloads', function (): void {
    expect(Filament\Tables\Table::make(new Josephdlmd\FilamentStorybook\Livewire\StoryCanvas)->hasLoadingSkeleton())->toBeTrue();
});

it('tells the browser which account fields hold the user\'s own email and name, only on the account pages', function (): void {
    $this->get('/fixture/profile')
        ->assertSee('autocomplete="name"', false)
        ->assertSee('autocomplete="email"', false);

    expect(Filament\Forms\Components\TextInput::make('email')->email()->getExtraInputAttributes())->toBe([]);

    auth()->logout();

    $this->get('/fixture/login')
        ->assertSee('autocomplete="username"', false)
        ->assertSee('autocomplete="current-password"', false);
});

it('says Log in and Log out, as Carbon does', function (): void {
    expect(__('filament-panels::auth/pages/login.heading'))->toBe('Log in')
        ->and(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))->toBe('Log in')
        ->and(__('filament-panels::layout.actions.logout.label'))->toBe('Log out');
});

it('asks for a 15-character password that has not appeared in a breach, and says why it refuses one', function (): void {
    // Have I Been Pwned answers with hash suffixes and counts; this one lists the SHA-1 of "correct horse battery staple".
    $leaked = 'correct horse battery staple';
    Illuminate\Support\Facades\Http::fake(['api.pwnedpasswords.com/*' => Illuminate\Support\Facades\Http::response(substr(strtoupper(sha1($leaked)), 5).':42')]);
    $errors = fn (string $password): array => Illuminate\Support\Facades\Validator::make(['password' => $password], ['password' => Illuminate\Validation\Rules\Password::default()])->errors()->all();

    expect($errors('short1'))->toBe(['Password must be 15 characters or more'])
        ->and($errors('a long enough passphrase'))->toBe([])
        ->and($errors($leaked))->toBe(['Choose a different password: this one has appeared in a data breach'])
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::minimumPasswordLength())->toBe(15);
});

it('keeps two-factor turned on when the email about it can\'t be sent', function (): void {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1]]);
    $this->actingAs(User::forceCreate(['name' => 'Ben', 'email' => 'ben@example.com', 'password' => 'secret']));

    // A mail server that is down throws from notify(); the hook logs it, so the change isn't shown as failed.
    Filament\Actions\Action::make('setUpAppAuthentication')->callAfter();

    expect(auth()->check())->toBeTrue();
});

it('words two-factor setup by the house rules and emails the owner when it is turned on or off', function (): void {
    Illuminate\Support\Facades\Notification::fake();
    $this->actingAs($user = User::forceCreate(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secret']));

    Filament\Actions\Action::make('setUpAppAuthentication')->callAfter();
    Filament\Actions\Action::make('disableAppAuthentication')->callAfter();
    Filament\Actions\Action::make('somethingElse')->callAfter();

    Illuminate\Support\Facades\Notification::assertSentToTimes($user, Josephdlmd\FilamentStorybook\Notifications\SignInMethodChanged::class, 2);
    expect(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.form.code.label'))->toBe('Security code')
        ->and(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.description'))->not->toContain('href')
        ->and(__('filament-panels::auth/multi-factor/app/provider.login_form.code.messages.invalid'))->toBe('Enter the code your authenticator app shows now')
        ->and(__('filament-panels::auth/multi-factor/app/actions/disable.modal.heading'))->toBe('Turn off authenticator app')
        ->and(__('filament-panels::auth/multi-factor/app/actions/set-up.label'))->toBe('Set up')
        ->and(__('filament-notifications::database.modal.empty.description'))->toBe('Messages about work that finished and records that need you appear here.')
        ->and(__('filament-panels::auth/pages/login.multi_factor.subheading'))->toBe('To continue logging in, you need to verify your identity.');
});

it('lets an invited user set their password once, from the latest link, within 7 days', function (): void {
    $user = User::forceCreate(['name' => 'Ben', 'email' => 'ben@example.com', 'password' => null, 'invited_at' => now()]);
    $link = fn (): string => (new Josephdlmd\FilamentStorybook\Stories\Invitations\UserInvitation)->toMail($user->fresh())->actionUrl;
    $works = function (string $url): bool {
        $request = Illuminate\Http\Request::create($url);
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        return Josephdlmd\FilamentStorybook\Stories\Invitations\AcceptInvitation::invitedUser($request) !== null;
    };
    $first = $link();

    $this->get($first)->assertOk()->assertSee('Set your password')->assertSee('ben@example.com')->assertSee('At least 15 characters');
    expect($works($first))->toBeTrue()
        ->and($works(str_replace('invitation/'.$user->id, 'invitation/999', $first)))->toBeFalse();

    $this->travel(8)->days();
    expect($works($first))->toBeFalse();
    $this->travelBack();

    // Sending again stops the first link.
    $user->forceFill(['invited_at' => now()->addMinute()])->save();
    expect($works($first))->toBeFalse()
        ->and($works($second = $link()))->toBeTrue();

    Illuminate\Support\Facades\Http::fake(['api.pwnedpasswords.com/*' => Illuminate\Support\Facades\Http::response('')]);
    Josephdlmd\FilamentStorybook\Tests\Fixtures\OpenInvitation::$user = $user->fresh();
    Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\OpenInvitation::class)
        ->fillForm(['password' => 'a long enough passphrase', 'passwordConfirmation' => 'a long enough passphrase'])
        ->call('resetPassword')
        ->assertRedirect(Filament\Facades\Filament::getUrl());

    expect(Illuminate\Support\Facades\Hash::check('a long enough passphrase', $user->fresh()->password))->toBeTrue()
        ->and(Filament\Facades\Filament::auth()->id())->toBe($user->id)
        // Used once: the link no longer works.
        ->and($works($second))->toBeFalse();
});

it('closes an invitation page opened from a link sent before the latest one', function (): void {
    $user = User::forceCreate(['name' => 'Ben', 'email' => 'ben@example.com', 'password' => null, 'invited_at' => now()]);
    Illuminate\Support\Facades\Http::fake(['api.pwnedpasswords.com/*' => Illuminate\Support\Facades\Http::response('')]);
    Josephdlmd\FilamentStorybook\Tests\Fixtures\OpenInvitation::$user = $user->fresh();
    $page = Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\OpenInvitation::class);

    $user->forceFill(['invited_at' => now()->addMinute()])->save();
    $page->fillForm(['password' => 'a long enough passphrase', 'passwordConfirmation' => 'a long enough passphrase'])
        ->call('resetPassword')
        ->assertNotified('This invitation no longer works');

    expect($user->fresh()->password)->toBeNull();
});

it('corrects Filament defaults that break a sourced rule, app-wide', function (): void {
    $delete = Filament\Actions\DeleteAction::make();

    expect($delete->getColor())->toBe('danger')
        ->and($delete->isOutlined())->toBeTrue()
        ->and($delete->getModalSubmitAction()->getColor())->toBe('danger')
        ->and($delete->getModalSubmitAction()->isOutlined())->toBeFalse()
        ->and(Filament\Actions\DeleteBulkAction::make()->isOutlined())->toBeTrue()
        ->and(__('filament-tables::table.fields.bulk_select_record.label', ['key' => 7]))->toBe('Select 7')
        ->and(__('filament-tables::table.fields.bulk_select_page.label'))->toBe('Select all on this page')
        ->and(__('filament-tables::table.selection_indicator.actions.deselect_all.label'))->toBe('Deselect all')
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::requiredMessage(Filament\Forms\Components\TextInput::make('name')))->toBe('Enter name')
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::requiredMessage(Filament\Forms\Components\TextInput::make('tin')->label('TIN')))->toBe('Enter TIN')
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::requiredMessage(Filament\Forms\Components\Select::make('payment_terms')))->toBe('Select payment terms');
});

it('keeps every table\'s filters, search and sort for the session', function (): void {
    $this->actingAs(Josephdlmd\FilamentStorybook\Tests\Fixtures\User::query()->create(['name' => 'Tester', 'email' => 'tester@example.com']));
    $table = Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages\ListGadgets::class)->instance()->getTable();

    expect($table->persistsFiltersInSession())->toBeTrue()
        ->and($table->persistsSearchInSession())->toBeTrue()
        ->and($table->persistsSortInSession())->toBeTrue();
});

it('keeps an error, or a toast with an action, open until dismissed, deciding when it is sent', function (): void {
    expect(Filament\Notifications\Notification::make()->success()->title('Saved')->getDuration())->toBe(6000)
        ->and(Filament\Notifications\Notification::make()->danger()->title('Order 1042 not saved')->getDuration())->toBe('persistent')
        ->and(Filament\Notifications\Notification::make()->success()->actions([Filament\Actions\Action::make('view')])->getDuration())->toBe('persistent')
        // An explicit choice set later still wins.
        ->and(Filament\Notifications\Notification::make()->danger()->duration(3000)->getDuration())->toBe(3000)
        ->and(Filament\Notifications\Notification::make()->success()->persistent()->getDuration())->toBe('persistent');

    // What send() stores; Filament 5.10.2 moved that store from the session to a mailbox.
    expect(Filament\Notifications\Notification::make()->danger()->title('Order 1042 not saved')->toArray()['duration'])->toBe('persistent');
});

it('labels actions with Carbon\'s verbs and titles their modals in sentence case', function (): void {
    $create = Filament\Actions\CreateAction::make()->model(Josephdlmd\FilamentStorybook\Tests\Fixtures\GadgetPart::class);

    expect(Filament\Actions\Action::make('markPaid')->requiresConfirmation()->getModalSubmitActionLabel())->toBe('Mark paid')
        ->and(Filament\Actions\Action::make('send')->schema([])->getModalSubmitActionLabel())->toBe('Send')
        ->and(Filament\Actions\Action::make('editDelivery')->label('Edit delivery details')->getModalSubmitActionLabel())->toBe('Save')
        ->and($create->getLabel())->toBe('New gadget part')
        ->and($create->getModalHeading())->toBe('New gadget part')
        ->and($create->getModalSubmitActionLabel())->toBe('Create')
        ->and(Filament\Actions\EditAction::make()->getModalSubmitActionLabel())->toBe('Save changes')
        ->and(Filament\Actions\DeleteAction::make()->getModalSubmitActionLabel())->toBe('Delete')
        ->and(Filament\Actions\DeleteBulkAction::make()->model(Josephdlmd\FilamentStorybook\Tests\Fixtures\GadgetPart::class)->getModalHeading())->toBe('Delete selected gadget parts')
        ->and(Filament\Actions\AttachAction::make()->getLabel())->toBe('Add')
        ->and(Filament\Actions\AssociateAction::make()->getModalSubmitActionLabel())->toBe('Add')
        ->and(Filament\Actions\DetachAction::make()->getLabel())->toBe('Remove')
        ->and(Filament\Actions\DetachBulkAction::make()->getLabel())->toBe('Remove selected')
        ->and(Filament\Actions\DissociateAction::make()->getModalSubmitActionLabel())->toBe('Remove')
        ->and(Filament\Actions\ReplicateAction::make()->getLabel())->toBe('Copy')
        ->and(Filament\Actions\ViewAction::make()->getModalCancelAction())->toBeNull()
        ->and(Filament\Actions\ViewAction::make()->hasModalCloseButton())->toBeTrue()
        // A stray click outside a form or a confirmation loses nothing; a View modal still closes that way.
        ->and(Filament\Actions\EditAction::make()->isModalClosedByClickingAway())->toBeFalse()
        ->and(Filament\Actions\DeleteAction::make()->isModalClosedByClickingAway())->toBeFalse()
        ->and(Filament\Actions\ViewAction::make()->isModalClosedByClickingAway())->toBeTrue()
        ->and(Filament\Actions\EditAction::make()->closeModalByClickingAway()->isModalClosedByClickingAway())->toBeTrue()
        ->and(__('filament-actions::detach.single.notifications.detached.title'))->toBe('Removed')
        ->and(__('filament-actions::attach.single.modal.actions.attach_another.label'))->toBe('Add & add another')
        // Lines the house defaults don't change are still Filament's.
        ->and(__('filament-actions::attach.single.modal.fields.record_id.label'))->toBe('Record')
        ->and(__('filament-actions::delete.single.label'))->toBe('Delete')
        ->and(trans_choice('filament-actions::import.notifications.completed.actions.download_failed_rows_csv.label', 3))->toBe('Download errors')
        ->and(__('filament-actions::import.failure_csv.system_error'))->not->toContain('please')
        ->and(__('filament-actions::import.notifications.completed.title'))->toBe('Import completed');

    // An import states its row limit before the upload, and keeps Filament's example file.
    $import = Filament\Actions\ImportAction::make()->importer(Josephdlmd\FilamentStorybook\Stories\Imports\SupplierImporter::class)->maxRows(5000)->livewire(new StoryCanvas);

    expect($import->getModalDescription()->toHtml())
        ->toStartWith('A CSV file of up to 5,000 rows. ')
        ->toContain('Download example CSV file');
});

it('labels a related list\'s actions by what the related records are, with the rows as records', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RelatedRecordsStories::class, 'story' => 'default'])
        ->assertSee('New contact')->assertSee('Delete')->assertDontSee('Remove');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RelatedRecordsStories::class, 'story' => 'linked'])
        ->assertSee('Add')->assertSee('Remove')->assertDontSee('Delete');
});

it('gives a resource sentence case where Filament title-cases its model', function (): void {
    $resource = new class extends Filament\Resources\Resource
    {
        use Josephdlmd\FilamentStorybook\Concerns\HasSentenceCaseLabels;

        protected static ?string $model = Josephdlmd\FilamentStorybook\Tests\Fixtures\GadgetPart::class;
    };

    expect($resource::getTitleCaseModelLabel())->toBe('gadget part')
        ->and($resource::getNavigationLabel())->toBe('Gadget parts')
        ->and($resource::getBreadcrumb())->toBe('Gadget parts')
        ->and(GadgetResource::getTitleCaseModelLabel())->toBe('Gadget');
});

it('says what global search searches, and what to try when it finds nothing', function (): void {
    $this->actingAs(User::forceCreate(['name' => 'Ana', 'email' => 'ana.search@example.com', 'password' => 'secret']));
    Filament\Facades\Filament::setCurrentPanel('fixture');
    event(new Filament\Events\ServingFilament);

    expect(__('filament-panels::global-search.field.placeholder'))->toBe('Search gadgets')
        ->and(__('filament-panels::global-search.no_results_message'))->toBe('No results. Check the spelling, or search for fewer words.')
        ->and(file_get_contents(__DIR__.'/../../resources/css/house.css'))->toContain(".fi-global-search-result-group-header {\n    text-transform: none;")
        ->and(file_get_contents(__DIR__.'/../../resources/js/house.js'))->toContain('`${total} ${total === 1 ? \'result\' : \'results\'}`');
});

it('heads global search groups in sentence case where the panel keeps Filament\'s own provider', function (): void {
    $results = Filament\GlobalSearch\GlobalSearchResults::make()->category('purchase orders')->category('TINs');

    expect(app(Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider::class))->toBeInstanceOf(Josephdlmd\FilamentStorybook\GlobalSearch\SentenceCaseGlobalSearchProvider::class)
        ->and(Josephdlmd\FilamentStorybook\GlobalSearch\SentenceCaseGlobalSearchProvider::sentenceCase($results)->getCategories()->keys()->all())->toBe(['Purchase orders', 'TINs']);
});

it('shows an error in plain words with a way out and its code small, in the panel\'s look, even outside every route', function (): void {
    Illuminate\Support\Facades\Route::get('/maintenance', fn () => abort(503));
    // A 500 often comes from the database being down, so its page must not need the database.
    Illuminate\Support\Facades\Route::get('/broken', function (): never {
        config(['database.connections.testing.database' => '/nonexistent/database.sqlite']);
        Illuminate\Support\Facades\DB::purge('testing');
        auth()->forgetUser();

        throw new RuntimeException('The database is down.');
    })->middleware('web');
    config(['app.debug' => false]);

    withErrorPages(function (): void {
        $this->artisan('vendor:publish', ['--tag' => 'filament-storybook-errors']);

        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSeeInOrder(['<title>', 'Page not found', '</title>', 'fi-simple-layout', '<h1 class="fi-simple-header-heading">', 'Page not found', 'Check the address for a typing mistake', 'Go to the dashboard', 'Error 404'], false)
            // No top bar: an error page leads out with its one button.
            ->assertDontSee('fi-simple-layout-header', false);

        $this->get('/maintenance')
            ->assertStatus(503)
            ->assertSeeInOrder(['Down for maintenance', 'Try again', 'Error 503']);

        $this->get('/broken')
            ->assertStatus(500)
            ->assertSeeInOrder(['Something went wrong', 'Go to the dashboard', 'Error 500']);
    });
});

it('keeps what the next record shares when creating another, and nothing else', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\FormContainerStories::class, 'story' => 'createAnother'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('create')->schemaComponent('story', 'form'))
        ->fillForm(['code' => 'I-0004', 'name' => 'Nitrile examination gloves, small', 'unit' => 'boxes'])
        ->callMountedAction(['another' => true])
        ->assertHasNoFormErrors()
        ->assertActionMounted(Filament\Actions\Testing\TestAction::make('create')->schemaComponent('story', 'form'))
        ->assertActionDataSet(['code' => null, 'name' => null, 'unit' => 'boxes']);

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\Item::query()->where('code', 'I-0004')->value('name'))->toBe('Nitrile examination gloves, small');
});
