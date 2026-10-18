<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Commands\InstallCommand;
use Josephdlmd\FilamentStorybook\Mcp\AuditPages;
use Josephdlmd\FilamentStorybook\Mcp\ListStories;
use Josephdlmd\FilamentStorybook\Mcp\ShowInstructions;
use Josephdlmd\FilamentStorybook\Mcp\ShowStories;
use Josephdlmd\FilamentStorybook\Mcp\TestStories;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\ChoiceStories;
use Josephdlmd\FilamentStorybook\Stories\ListTableStories;
use Josephdlmd\FilamentStorybook\Stories\MoneyColumnStories;
use Josephdlmd\FilamentStorybook\Stories\MoneyInputStories;
use Josephdlmd\FilamentStorybook\Stories\RecordFormStories;
use Josephdlmd\FilamentStorybook\Stories\StatusBadgeStories;
use Josephdlmd\FilamentStorybook\Stories\ContactStories;
use Josephdlmd\FilamentStorybook\Stories\PercentageStories;
use Josephdlmd\FilamentStorybook\Stories\TinInputStories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;

beforeEach(function (): void {
    $this->actingAs(User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => 'secret']));
});

it('discovers the shipped stories, sorted by title', function (): void {
    expect(array_keys(app(StoryRegistry::class)->all()))
        ->toBe(['actions-adjust-stock', 'actions-approve-and-reject', 'actions-archive', 'actions-create-next-document', 'actions-credit-limit', 'actions-delete', 'actions-download-pdf', 'actions-download-supplier-statement', 'actions-email-document', 'actions-invite-a-user', 'actions-mark-2307-received', 'actions-record-payment', 'actions-void', 'dashboards-key-figures-and-charts', 'feedback-notifications', 'forms-choice', 'forms-contact-details', 'forms-date', 'forms-document-totals', 'forms-file-upload', 'forms-form-container', 'forms-line-items', 'forms-money-input', 'forms-percentage', 'forms-quantity', 'forms-record-form', 'forms-record-picker', 'forms-settings-page', 'forms-tin', 'forms-unit-cost', 'forms-yes-or-no', 'records-details', 'records-document-lifecycle', 'records-history', 'records-key-figures', 'records-locked', 'records-paper-receipt', 'records-previous-and-next', 'records-related-records', 'records-stock', 'records-view-page', 'reports-collections', 'reports-purchases-and-payables', 'reports-receivables-ageing', 'reports-sales', 'reports-tax-withheld-by-customers', 'reports-tax-withheld-from-suppliers', 'tables-bulk-actions', 'tables-import-and-export', 'tables-list-table', 'tables-money-column', 'tables-status-badge']);
});

it('describes a story the way a components manifest does', function (): void {
    $manifest = app(StoryRegistry::class)->manifest(app(MoneyInputStories::class));

    expect($manifest['imports'])->toBe(['Filament\Forms\Components\TextInput'])
        ->and($manifest['stories'][0])->toMatchArray([
            'name' => 'default',
            'label' => 'Default',
            'kind' => 'component',
            'snippet' => "TextInput::make('amount')\n    ->label('Amount (optional)')\n    ->numeric()\n    ->prefix('₱')\n    ->step(0.01)\n    ->minValue(0)\n    ->rules(['decimal:0,2'])\n    ->maxWidth('xs')\n    ->validationMessages(['decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50'])",
        ])
        ->and($manifest['stories'][1]['description'])->toBe('A required amount says what to do when it is left empty.');
});

it('reads the canvas kind from the return type', function (): void {
    expect(Stories::kind(new ReflectionMethod(MoneyColumnStories::class, 'default')))->toBe('column')
        ->and(Stories::kind(new ReflectionMethod(ChoiceStories::class, 'fewOptions')))->toBe('component')
        ->and(Stories::kind(new ReflectionMethod(ListTableStories::class, 'default')))->toBe('table')
        ->and(Stories::kind(new ReflectionMethod(RecordFormStories::class, 'default')))->toBe('schema');
});

it('renders every story', function (): void {
    foreach (app(StoryRegistry::class)->all() as $stories) {
        foreach (array_keys($stories->stories()) as $story) {
            Livewire::test(StoryCanvas::class, ['stories' => $stories::class, 'story' => $story])->assertOk();
        }
    }
});

it('shows column stories with their sample rows', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => MoneyColumnStories::class, 'story' => 'default'])
        ->assertSee('-₱320.00');
});

it('accepts a TIN typed any reasonable way and stores one format', function (string $typed, ?string $stored): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => TinInputStories::class, 'story' => 'required'])->set('data.tin', $typed);

    $stored !== null
        ? expect($canvas->instance()->form->getState())->toBe(['tin' => $stored])
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, 'Enter a TIN in the correct format');
})->with([
    'dashes, head office' => ['123-456-789-000', '123-456-789-000'],
    'spaces, branch' => ['123 456 789 002', '123-456-789-002'],
    'digits only' => ['123456789000', '123-456-789-000'],
    'dots and stray spaces' => [' 123.456.789.000 ', '123-456-789-000'],
    'missing branch code' => ['123-456-789', null],
    'letters' => ['ABC-456-789-000', null],
]);

it('accepts a Philippine phone number typed any reasonable way and stores it as +63', function (string $typed, ?string $stored): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => ContactStories::class, 'story' => 'phone'])->set('data.phone', $typed);

    $stored !== null
        ? expect($canvas->instance()->form->getState())->toBe(['phone' => $stored])
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, 'Enter a phone number, like 0917 123 4567');
})->with([
    'mobile' => ['0917 123 4567', '+63 917 123 4567'],
    'mobile, +63 and dashes' => ['+63 917-123-4567', '+63 917 123 4567'],
    'mobile, +63 and the 0' => ['+63 0917 123 4567', '+63 917 123 4567'],
    'Metro Manila landline' => ['(02) 8123 4567', '+63 2 8123 4567'],
    'provincial landline' => ['(032) 123-4567', '+63 32 123 4567'],
    'area code 63' => ['(063) 221 1234', '+63 63 221 1234'],
    'too short' => ['0917 123 456', null],
    'foreign' => ['+1 415 555 0100', null],
]);

it('accepts a percentage with or without a typed % and up to 2 decimals', function (string $typed, ?string $stored, ?string $error = null): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => PercentageStories::class, 'story' => 'default'])->set('data.discount', $typed);

    $stored !== null
        ? expect($canvas->instance()->form->getState())->toBe(['discount' => $stored])
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, $error);
})->with([
    'plain' => ['12.5', '12.5'],
    'typed %' => ['12.5 %', '12.5'],
    'three decimals' => ['12.125', null, 'Enter the discount with at most 2 decimal places'],
    'over 100' => ['120', null, 'Discount must be 100 or less'],
]);

it('checks a TIN in its stored form, so unique() catches one typed without dashes', function (): void {
    $tin = (new Josephdlmd\FilamentStorybook\Stories\TinInputStories)->required();

    expect($tin->mutateStateForValidation('123456789000'))->toBe('123-456-789-000')
        ->and($tin->mutateStateForValidation(' 123.456 789-000 '))->toBe('123-456-789-000')
        // Not a TIN: left as typed, for the format error.
        ->and($tin->mutateStateForValidation('123-456-789'))->toBe('123-456-789');
});

it('records a supplier\'s invoice only once it is confirmed as a proper VAT invoice', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentTotalsStories::class, 'story' => 'supplierInvoice'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordSupplierInvoice')->schemaComponent('story', 'form'))
        ->fillForm(['supplier_invoice_number' => 'BT-20931', 'supplier_invoice_date' => today()->toDateString(), 'total' => '5298.00', 'vat' => '567.64', 'is_vat_invoice' => false])
        ->callMountedAction()
        ->assertHasFormErrors(['is_vat_invoice' => 'accepted']);
});

it('warns, without blocking, when the printed VAT is more than ₱0.05 from 12/112 of the VATable total', function (string $vat, bool $warned): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentTotalsStories::class, 'story' => 'supplierInvoice'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordSupplierInvoice')->schemaComponent('story', 'form'))
        ->fillForm(['supplier_invoice_number' => 'BT-20931', 'supplier_invoice_date' => today()->toDateString(), 'total' => '5298.00', 'vat' => $vat, 'is_vat_invoice' => true]);

    // Only visible components are listed. 12/112 of ₱5,298.00 is ₱567.6429.
    $shown = collect($canvas->instance()->getSchema('mountedActionSchema0')->getFlatComponents())
        ->contains(fn ($component): bool => $component instanceof Filament\Schemas\Components\Callout);

    expect($shown)->toBe($warned);
    $canvas->callMountedAction()->assertHasNoFormErrors();
})->with([
    'as worked out' => ['567.64', false],
    'within ₱0.05' => ['567.69', false],
    'over ₱0.05' => ['576.40', true],
]);

it('withholds at the rate the codes table gives, not one written into the app', function (): void {
    // The sample database lives for the whole test run, so the rate goes back afterwards.
    $code = Josephdlmd\FilamentStorybook\Stories\Documents\WithholdingCode::find('WC158');
    $code->update(['rate' => '1.50']);

    try {
        $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentTotalsStories::class, 'story' => 'supplierInvoice'])
            ->mountAction(Filament\Actions\Testing\TestAction::make('recordSupplierInvoice')->schemaComponent('story', 'form'))
            ->fillForm(['total' => '5298.00', 'vat' => '567.64']);

        // 1.5% of ₱4,730.36 is ₱70.9554.
        expect($canvas->instance()->getSchema('mountedActionSchema0')->getComponent('tax_withheld')->getState())->toBe('70.96');
    } finally {
        $code->update(['rate' => '1.00']);
    }
});

it('refuses to render a class that is not a registered story', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => User::class, 'story' => 'delete'])->assertNotFound();
});

it('shows the catalogue page with navigation, live story and snippet', function (): void {
    $this->get('/fixture/storybook?component=forms-money-input')
        ->assertOk()
        ->assertSee('Money input')
        ->assertSee('Status badge')
        ->assertSee('Enter the amount', false);

    expect(Josephdlmd\FilamentStorybook\Pages\Storybook::shouldRegisterNavigation())->toBeFalse();
});

it('titles the page without its group and shows code in descriptions as code', function (): void {
    $this->get('/fixture/storybook?component=tables-status-badge')
        ->assertDontSee('Tables/Status badge')
        ->assertSee('<code>-&gt;badge()</code>', false);
});

it('lists the patterns for agents', function (): void {
    expect((string) (new ListStories)->handle(new Request)->content())
        ->toContain('`forms-money-input` Forms/Money input: Every peso amount a user types: pesos and centavos, at most 2 decimal places')
        ->toContain('(stories: default, required)');
});

it('shows a pattern for agents with imports, snippets and a preview link', function (): void {
    expect((string) (new ShowStories)->handle(new Request(['id' => 'tables-money-column']))->content())
        ->toContain('use Filament\Tables\Columns\TextColumn;')
        ->toContain("TextColumn::make('amount')")
        ->toContain('/fixture/storybook?component=tables-money-column');

    expect((new ShowStories)->handle(new Request(['id' => 'nope']))->isError())->toBeTrue();
});

it('gives agents the real signatures of the methods a pattern uses', function (): void {
    $api = collect(app(StoryRegistry::class)->manifest(app(StatusBadgeStories::class))['api'])->pluck('signature', 'method');

    expect($api->keys()->all())->toBe(['TextColumn::make', 'TextColumn::badge'])
        ->and($api['TextColumn::badge'])->toBe('badge(Closure|bool $condition = true): static');

    expect((string) (new ShowStories)->handle(new Request(['id' => 'tables-status-badge']))->content())
        ->toContain("## API")
        ->toContain('TextColumn::badge(Closure|bool $condition = true): static')
        ->toContain('enum DocumentStatus: string implements HasColor, HasLabel');
});

it('takes a status badge\'s word and colour from the status enum', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => StatusBadgeStories::class, 'story' => 'default'])->call('loadTable');
    $column = $canvas->instance()->getTable()->getColumn('status');

    expect(collect($canvas->instance()->getTable()->getRecords())->map(fn ($record): array => [$column->record($record)->formatState($column->getState()), $column->getColor($column->getState())])->values()->all())
        ->toBe([['Draft', 'gray'], ['For approval', 'info'], ['Posted', 'success'], ['Void', 'danger']]);
});

it('credits each method in a screen story to the class it is called on', function (): void {
    $api = collect(app(StoryRegistry::class)->manifest(app(ListTableStories::class))['api'])->pluck('signature', 'method');

    expect($api->keys()->all())->toContain('Table::columns', 'TextColumn::make', 'TextColumn::money', 'SelectFilter::options', 'Table::persistFiltersInSession')
        ->not->toContain('Table::make', 'Table::money', 'TextColumn::columns');
});

it('renders a screen story as the resource would, with its sample rows', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => ListTableStories::class, 'story' => 'default'])
        ->assertSee('SUP-0003')
        ->assertSee('₱1,250,000.50');

    Livewire::test(StoryCanvas::class, ['stories' => RecordFormStories::class, 'story' => 'default'])
        ->assertSet('data.tin', '123-456-789-000')
        ->assertSee('For example, 123-456-789-000');
});

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

    Filament\Notifications\Notification::make()->danger()->title('Order 1042 not saved')->send();

    expect(session('filament.notifications')[0]['duration'])->toBe('persistent');
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

it('tells an agent the valid ids instead of letting it guess', function (): void {
    expect((string) (new ShowStories)->handle(new Request(['id' => 'nope']))->content())
        ->toContain('Valid ids: actions-adjust-stock, actions-approve-and-reject, actions-archive, actions-create-next-document, actions-credit-limit, actions-delete, actions-download-pdf, actions-download-supplier-statement, actions-email-document, actions-invite-a-user, actions-mark-2307-received, actions-record-payment, actions-void, dashboards-key-figures-and-charts, feedback-notifications, forms-choice, forms-contact-details, forms-date, forms-document-totals, forms-file-upload, forms-form-container, forms-line-items, forms-money-input, forms-percentage, forms-quantity, forms-record-form, forms-record-picker, forms-settings-page, forms-tin, forms-unit-cost, forms-yes-or-no, records-details, records-document-lifecycle, records-history, records-key-figures, records-locked, records-paper-receipt, records-previous-and-next, records-related-records, records-stock, records-view-page, reports-collections, reports-purchases-and-payables, reports-receivables-ageing, reports-sales, reports-tax-withheld-by-customers, reports-tax-withheld-from-suppliers, tables-bulk-actions, tables-import-and-export, tables-list-table, tables-money-column, tables-status-badge');
});

it('labels a related list\'s actions by what the related records are, with the rows as records', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RelatedRecordsStories::class, 'story' => 'default'])
        ->assertSee('New contact')->assertSee('Delete')->assertDontSee('Remove');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RelatedRecordsStories::class, 'story' => 'linked'])
        ->assertSee('Add')->assertSee('Remove')->assertDontSee('Delete');
});

it('imports a CSV row with the form\'s checks, errors and stored formats, and shows agents the importer to copy', function (): void {
    Illuminate\Support\Facades\Schema::create('suppliers', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('tin')->unique();
        $table->decimal('credit_limit', 15, 2)->nullable();
        $table->string('atc')->nullable();
        $table->timestamps();
    });

    $import = new Josephdlmd\FilamentStorybook\Stories\Imports\SupplierImporter(new Filament\Actions\Imports\Models\Import, ['name' => 'name', 'tin' => 'tin', 'credit_limit' => 'credit_limit', 'withholdingCode' => 'atc'], []);

    $import(['name' => 'Abra Medical Supply', 'tin' => '123 456 789 000', 'credit_limit' => '250000.50']);
    // The same TIN, typed another way, updates that supplier.
    $import(['name' => 'Abra Medical Supply Inc.', 'tin' => '123.456.789.000', 'credit_limit' => '250000.50']);

    // The amount reaches the model as typed, never a float (SQLite itself reads decimals back as floats).
    expect(Josephdlmd\FilamentStorybook\Stories\Imports\Supplier::query()->get(['name', 'tin'])->toArray())
        ->toBe([['name' => 'Abra Medical Supply Inc.', 'tin' => '123-456-789-000']])
        ->and($import->getData()['credit_limit'])->toBe('250000.50')
        ->and(fn () => $import(['name' => 'Benguet Trading', 'tin' => '123-456-789', 'credit_limit' => '1']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the TIN as 12 digits, such as 123-456-789-000')
        // Refused by the form, so refused here too, never stripped down to its digits.
        ->and(fn () => $import(['name' => 'Benguet Trading', 'tin' => '123456789000X', 'credit_limit' => '1']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the TIN as 12 digits, such as 123-456-789-000')
        ->and(fn () => $import(['name' => 'Benguet Trading', 'tin' => '987-654-321-000', 'credit_limit' => '1.234']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the credit limit with at most 2 decimal places')
        // A related record by its code: found however it is typed, and refused with the house wording when unknown.
        ->and(fn () => $import(['name' => 'Benguet Trading', 'tin' => '987-654-321-000', 'atc' => 'WC999']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter an ATC we withhold, such as WC158, or leave it empty');

    $import(['name' => 'Benguet Trading', 'tin' => '987-654-321-000', 'atc' => ' wc158 ']);

    expect(Josephdlmd\FilamentStorybook\Stories\Imports\Supplier::query()->where('tin', '987-654-321-000')->value('atc'))->toBe('WC158');

    expect((string) (new ShowStories)->handle(new Request(['id' => 'tables-import-and-export']))->content())
        ->toContain('## SupplierImporter')
        ->toContain('public function resolveRecord(): Supplier');
});

/** Runs storybook-test under Filament's compiled theme, which apps load; the tests have no published one, and unstyled buttons fail WCAG 2.2's target size. */
function testStoriesWithTheme(array $arguments = []): string
{
    $theme = public_path('css/filament/filament/app.css');
    @mkdir(dirname($theme), recursive: true);
    copy(__DIR__.'/../../vendor/filament/filament/dist/theme.css', $theme);

    try {
        return (string) (new TestStories)->handle(new Request($arguments))->content();
    } finally {
        unlink($theme);
    }
}

it('reports every story passing', function (): void {
    expect(testStoriesWithTheme())
        ->toStartWith('# 99 passing, 0 failing, 0 warnings, 0 accessibility issues')
        ->toContain('- `forms-money-input.required`');
});

it('passes every check on the patterns taken from the accounting app', function (): void {
    expect(testStoriesWithTheme(['ids' => ['records-previous-and-next', 'records-key-figures', 'dashboards-key-figures-and-charts', 'forms-record-picker', 'reports-sales', 'reports-collections', 'reports-receivables-ageing']]))
        ->toStartWith('# 11 passing, 0 failing, 0 warnings, 0 accessibility issues');
});

it('names every line-item field under Filament\'s own CSS, which hides the cell labels once the table shows headers', function (): void {
    expect(testStoriesWithTheme(['ids' => ['forms-line-items']]))
        ->toStartWith('# 2 passing, 0 failing, 0 warnings, 0 accessibility issues');
});

it('reports a failing story with the line to fix, so the agent can self-correct', function (): void {
    app(StoryRegistry::class)->discover(__DIR__.'/../Fixtures/Stories', 'Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories');

    expect((string) (new TestStories)->handle(new Request(['ids' => ['broken-story']]))->content())
        ->toStartWith('# 14 passing, 3 failing, 27 warnings, 1 accessibility issues')
        ->toContain('- `broken-story.vagueAndTitleCase`: a keyboard shortcut needs Ctrl, ⌘ or Alt')
        ->toContain('- `broken-story.collapsedFilters`: AboveContentCollapsible puts the filter button alone')
        ->toContain('- `broken-story.works`: the field `name` is neither required() nor labelled "(optional)"')
        ->toContain('- `broken-story.nonNullableRecord`: `fn (Model $record)` gets null while the table has no rows')
        ->toContain('- `broken-story.vagueAndTitleCase`: "Lorem ipsum" is placeholder content')
        ->toContain('- `broken-story.vagueAndTitleCase`: a button labelled "Submit"')
        ->toContain('- `broken-story.vagueAndTitleCase`: "Save Order" is in Title Case')
        ->toContain('- `broken-story.vagueAndTitleCase`: a notification title has no full stop')
        ->toContain('- `broken-story.unnamedIconButton`: [critical] button-name: Buttons must have discernible text')
        ->toContain('- `broken-story`: add description()')
        ->toContain('- `broken-story.works`: the field `name` has a placeholder()')
        ->toContain('- `broken-story.floatMoney`: round() works on floats')
        ->toContain('- `broken-story.ownSpacing`: sets its own padding or margin')
        ->toContain('- `broken-story.ownTypeAndColour`: sets its own font size, weight or colour')
        ->toContain('- `broken-story.hoverOnlyTooltip`: a tooltip on a column, entry or hint icon shows on mouse hover only')
        ->toContain('- `broken-story.iconOnlyAction`: an icon-only action needs a tooltip')
        ->toContain('- `broken-story.successCallout`: a callout is info or warning only')
        ->toContain('- `broken-story.nestedMenus`: a menu opens inside another menu')
        ->toContain('- `broken-story.crampedChoices`: stack radio and checkbox options, without inline()')
        ->toContain('- `broken-story.openCollapsible`: a collapsible section starts collapsed')
        ->toContain('- `broken-story.crampedChoices`: the wizard step "Choose the supplier now" is longer than 16 characters')
        ->toContain('- `broken-story.throws`: add a one-line docblock')
        ->toContain('- `broken-story.failsWhileRendering`: Error: Call to a member function upper() on string at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:53')
        ->toContain('- `broken-story.throws`: RuntimeException: Story exploded at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:37')
        ->toContain('- `broken-story.untyped`: Not a story: return a Filament component, table column or action, or take and return a Table or Schema, at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:40')
        ->toContain('run storybook-test again until every story passes')
        ->toContain('- `broken-story.works`');

    expect((new TestStories)->handle(new Request(['ids' => ['nope']]))->isError())->toBeTrue();
});

it('says how to enable the accessibility check when it cannot run', function (): void {
    config(['filament-storybook.chromium' => '/nonexistent/chrome']);

    expect((string) (new TestStories)->handle(new Request(['ids' => ['forms-tin']]))->content())
        ->toStartWith('# 2 passing, 0 failing, 0 warnings, 0 accessibility issues')
        ->toContain('Accessibility not checked (')
        ->toContain('npx playwright-core install chromium');
});

it('gives agents the story-writing rules', function (): void {
    expect((string) (new ShowInstructions)->handle(new Request)->content())
        ->toContain('# Writing Filament stories')
        ->toContain('## Choosing the component')
        ->toContain('## Fit the other patterns')
        ->toContain('Run `storybook-test`')
        ->toContain('# Filament coverage')
        ->toContain('| `KeyValue` |')
        ->toContain('## Business standards (from the owner)');
});

/** Agents look up each component in the coverage table, so a component Filament adds must get a row before it is used. */
it('lists every component Filament ships in the coverage table', function (): void {
    $coverage = file_get_contents(__DIR__.'/../../docs/filament-coverage.md');
    $dirs = ['forms/src/Components', 'infolists/src/Components', 'tables/src/Columns', 'tables/src/Filters', 'schemas/src/Components', 'actions/src'];

    $missing = collect($dirs)
        ->flatMap(fn (string $dir): array => glob(__DIR__."/../../vendor/filament/{$dir}/*.php"))
        ->reject(fn (string $file): bool => (bool) preg_match('/^(abstract class|interface|trait) /m', file_get_contents($file)))
        ->map(fn (string $file): string => basename($file, '.php'))
        ->reject(fn (string $class): bool => str_contains($coverage, "`{$class}`"))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('joins the Boost MCP server when laravel/mcp is installed', function (): void {
    expect(config('boost.mcp.tools.include'))->toBe([ListStories::class, ShowStories::class, TestStories::class, ShowInstructions::class, AuditPages::class]);

    // A fatal error in any tool class takes down every tool in Boost's server, so each must load.
    expect(collect(config('boost.mcp.tools.include'))->map(fn (string $tool): string => (new $tool)->name())->all())
        ->toBe(['storybook-list', 'storybook-show', 'storybook-test', 'storybook-instructions', 'storybook-audit']);
});

it('audits the real resource screens against the house rules', function (): void {
    $report = (string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content();

    expect($report)
        ->toStartWith('# 2 pages rendered, 0 failing, 54 warnings')
        ->toContain('- `CreateGadget`: the upload `manual` shows a file by the random name it is stored under')
        ->toContain('- `CreateGadget`: its fields have their labels beside them (`released_on`); put labels above their fields')
        ->toContain('- `CreateGadget`: the select `size` offers 3 options; show them as stacked radios')
        ->not->toContain('the select `warehouse_id`')
        ->toContain('- `CreateGadget`: the select `features` offers 2 options to pick several from; show them as a `CheckboxList`')
        ->toContain('- `ListGadgets`: the action "Print Gadget Labels" is in Title Case')
        ->not->toContain('"Report For May 2026"')
        ->toContain('- `ListGadgets`: the action `report` runs on the key `r` alone')
        ->toContain('- `CreateGadget`: its form lays fields out in several columns')
        ->toContain('Signed in as tester@example.com.')
        ->toContain('- `ListGadgets`: the money column `price` must be right-aligned')
        ->toContain('- `ListGadgets`: the column `price` has a tooltip, which shows on mouse hover only')
        ->toContain('- `ListGadgets`: 4 buttons in the header; show at most three')
        ->toContain('- `ListGadgets`: 7 actions above the table; keep five at most')
        ->toContain('- `ListGadgets`: a row menu holds only 2 action(s); show them inline')
        ->toContain('- `CreateGadget`: the section "Stock Keeping" can collapse but starts open')
        ->toContain('- `ListGadgets`: the icon-only action `print` has no tooltip')
        ->toContain('- `CreateGadget`: the field `serial` explains itself in a hint icon\'s tooltip')
        ->toContain('- `CreateGadget`: the optional field "Owner" doesn\'t say so')
        ->toContain('- `CreateGadget`: the radio group `colour` is optional but can\'t be cleared once picked')
        ->toContain('- `CreateGadget`: the options of `colour` sit side by side')
        ->not->toContain('the options of `finish`')
        ->not->toContain('`fitting_count`')
        ->not->toContain('the field `fittings` hides its label')
        ->toContain('- `CreateGadget`: the options of `accessories` sit side by side')
        ->toContain('- `CreateGadget`: the field `description` is a rich-text or Markdown editor')
        ->toContain('- `ListGadgets`: 3 row actions are inline; keep at most two')
        ->toContain('- `ListGadgets`: bulk actions are in a "Bulk actions" menu')
        ->toContain('- `ListGadgets`: its columns reorder only by dragging')
        ->toContain('- `ListGadgets`: several filters are hidden in a dropdown')
        ->toContain('- `ListGadgets`: the empty list gives no next step')
        ->toContain('- `CreateGadget`: the field `code` uses placeholder()')
        ->toContain('- `CreateGadget`: the field `support_email` holds an email address but isn\'t `email()`')
        ->toContain('- `CreateGadget`: the field `support_phone` holds a phone number but isn\'t `tel()`')
        ->toContain('- `CreateGadget`: the field `is_active` is a toggle in a form that is saved')
        ->toContain('- `CreateGadget`: the date field `released_on` uses `native(false)`')
        ->toContain('- `CreateGadget`: the upload `manual` does not state its accepted types and largest size')
        ->toContain('- `CreateGadget`: the error "Please enter the serial" doesn\'t say what to do')
        ->toContain('- `CreateGadget`: the column "Qty" in `parts` heads the field labelled "Quantity"')
        ->toContain('- `CreateGadget`: the field `quantity` hides its label')
        ->toContain('- `CreateGadget`: the line-item field `note` has no column in `parts`')
        ->toContain('- `CreateGadget`: `parts` reorders only by dragging')
        ->toContain('- `CreateGadget`: the read-only field `sku` is still saved')
        ->toContain('- `CreateGadget`: the select `owner_id` lists other records but can\'t be searched')
        ->toContain('- `ListGadgets`: `ship` has a button labelled "Submit"')
        ->toContain('- `ListGadgets`: the action "Return Gadget" is in Title Case')
        ->toContain('- `ListGadgets`: the column "Created At" is in Title Case')
        ->toContain('- `ListGadgets`: `archive` asks "Are you sure you would like to do this?"')
        ->toContain('- `ListGadgets`: `delete` is labelled "Remove selected", but it destroys the record')
        ->toContain('- `CreateGadget`: the field label "Released On" is in Title Case')
        ->toContain('- `CreateGadget`: the field label "Code:" ends with a colon')
        ->toContain('- `CreateGadget`: the heading "Stock Keeping" is in Title Case')
        ->toContain('- `CreateGadget`: "Create Gadget" (the page title, navigation or breadcrumb) is in Title Case; add `use \\Josephdlmd\\FilamentStorybook\\Concerns\\HasSentenceCaseLabels;` inside `GadgetResource`')
        ->not->toContain('"Gadgets"')
        // Sentence case, names, acronyms and Filament's own labels raise nothing.
        ->not->toContain('"SKU"')
        ->not->toContain('"Price"')
        ->not->toContain('"Archive"')
        // A line-item field built right, under its own label as the header, raises nothing.
        ->not->toContain('`part`')
        ->not->toContain('"Part"')
        ->not->toContain('the error "Enter name"')
        ->not->toContain('25 rows')
        ->toContain('- `EditGadget`: no record to open')
        ->toContain('- `ViewGadget`: no record to open')
        ->toContain('Fix each one, then run storybook-audit again');
});

it('audits the dashboard\'s widgets on a full audit', function (): void {
    expect((string) (new AuditPages)->handle(new Request)->content())
        ->toContain('- `GadgetStats`: it reloads itself every 5s, and the user can\'t stop it')
        ->toContain('- `GadgetSalesChart`: its legend hides a series on a mouse click')
        ->toContain('- `GadgetSalesChart`: its 2 series share one colour')
        ->toContain('- `GadgetStockChart`: it has one series but shows a legend')
        ->not->toContain('- `GadgetStockChart`: its legend hides')
        ->toContain('- `OrdersPerMonthChart`')
        ->not->toContain('- `OrdersPerMonthChart`:');
});

it('audits the panel it is asked for, and warns when that panel has no StorybookPlugin', function (): void {
    expect((string) (new AuditPages)->handle(new Request(['panel' => 'shop']))->content())
        ->toContain('- `panel`: the shop panel has no `StorybookPlugin`')
        ->not->toContain('GadgetResource');

    expect((string) (new AuditPages)->handle(new Request)->content())->not->toContain('- `panel`:');

    expect((string) (new AuditPages)->handle(new Request(['panel' => 'stores']))->content())
        ->toContain('No panel "stores". The panels are: fixture, shop.');
});

it('treats a row of filters that apply as they change as a filter bar, and lets product names keep their capital', function (): void {
    $audit = (string) (new AuditPages)->handle(new Request)->content();

    expect($audit)
        ->toContain('- `GadgetReport`')
        ->not->toContain('- `GadgetReport`: its form lays fields out in several columns')
        ->not->toContain('- `GadgetReport`: the optional field')
        ->not->toContain('- `GadgetReport`: the select `year`')
        ->not->toContain('- `GadgetReport`: the action "Download Excel"');

    expect(AuditPages::isTitleCase('Download Excel', 'Download Excel'))->toBeFalse()
        ->and(AuditPages::isTitleCase('Export To Excel', 'Export To Excel'))->toBeTrue()
        ->and(AuditPages::isTitleCase('Print Gadget Labels', 'Print Gadget Labels'))->toBeTrue();
});

it('opens a custom page of one record on the first record', function (): void {
    Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5]);

    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->toContain('- `CheckGadget`')
        ->not->toContain('- `CheckGadget`: it needs route parameters');
});

it('audits the panel\'s own pages on a full audit, following a page that sends the user back to itself as it opens', function (): void {
    expect((string) (new AuditPages)->handle(new Request)->content())
        ->toContain('- `GadgetOverview`')
        ->not->toContain('- `GadgetOverview`: tester@example.com may not open it')
        ->not->toContain('- `Storybook`');

    // A screen-by-screen audit leaves the panel's own pages alone.
    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->not->toContain('`GadgetOverview`');
});

it('audits the panel\'s menu on a full audit: two levels at most, groups in sentence case, words always on screen', function (): void {
    expect((string) (new AuditPages)->handle(new Request)->content())
        ->toContain('- `navigation`: the sidebar shrinks to icons only')
        ->toContain('- `navigation`: the group "Stock Control" is in Title Case')
        ->toContain('- `navigation`: "Export gadgets" names an action; name the place')
        ->toContain('- `navigation`: "Export gadgets" shows a count')
        ->toContain('- `navigation`: dark mode is off')
        ->toContain('- `navigation`: "Gadgets" in "Stock Control" has items under it, a third level')
        ->not->toContain('"Help"');

    // A screen-by-screen audit leaves the menu alone.
    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->not->toContain('`navigation`');
});

it('reports a menu that fails to build, such as a visibility check querying a missing table, instead of stopping the audit', function (): void {
    Filament\Facades\Filament::getPanel('fixture')->navigationItems([Filament\Navigation\NavigationItem::make('Broken')->visible(fn () => throw new RuntimeException('no such table: orders'))->url('#')]);

    expect((string) (new AuditPages)->handle(new Request)->content())
        ->toContain('- `navigation`: RuntimeException: no such table: orders at ');
});

it('audits edit pages against an existing record', function (): void {
    Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5]);

    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->toStartWith('# 7 pages rendered, 0 failing, 94 warnings')
        ->toContain('- `EditGadget`: its form lays fields out in several columns')
        // Related lists are audited too, read from the resource, since a View page may embed them itself.
        ->toContain('- `PartsRelationManager`: the column "Created At" is in Title Case')
        ->toContain('- `PartsRelationManager`: the empty list offers 2 actions')
        ->toContain('- `PartsRelationManager`: the table shows 2 column(s); a table needs three or more')
        ->toContain('- `PartsRelationManager`: the empty list\'s button is a second primary')
        ->toContain('- `PartsRelationManager`: `delete` is labelled "Remove", but it destroys the record')
        ->toContain('- `PartsRelationManager`: `delete` asks "Are you sure you would like to do this?"')
        ->not->toContain('- `SparePartsRelationManager`:')
        ->toContain('- `EditGadget`: `delete` is a solid danger button beside other actions')
        ->toContain('- `EditGadget`: `delete` asks "Are you sure you would like to do this?"')
        ->toContain('- `ViewGadget`: it shows the record as a disabled form')
        ->toContain('- `ViewGadget`: its related lists are tabs')
        // The view page's disabled form isn't audited field by field; the warning above covers it.
        ->not->toContain('- `ViewGadget`: the field')
        ->toContain('- `EditGadget`: the field `code` uses placeholder()');
});

it('checks a page\'s open modals, its form showing errors and the whole page, not only its content, saving nothing', function (): void {
    $gadget = Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5]);
    $states = fn (string $key, $page): array => (new ReflectionMethod(AuditPages::class, 'states'))->invoke(null, $key, $page);

    expect($states('EditGadget', Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages\EditGadget::class, ['record' => $gadget->getKey()])))
        ->toHaveKey('EditGadget, delete open')
        ->and($states('EditGadget', Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages\EditGadget::class, ['record' => $gadget->getKey()]))['EditGadget, delete open'])->toContain('fi-modal')
        ->and($creating = $states('CreateGadget', Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages\CreateGadget::class)))->toHaveKey('CreateGadget, with errors')
        ->and($creating['CreateGadget, with errors'])->toContain('data-validation-error')
        ->and(Gadget::count())->toBe(1)
        ->and((new ReflectionMethod(AuditPages::class, 'layout'))->invoke(null, Filament\Facades\Filament::getPanel('fixture')))->toContain('<html')->toContain('<title>');
});

it('reports content cut off at 320 pixels wide, and checks a whole page\'s title and language', function (): void {
    [$issues, $unchecked] = Josephdlmd\FilamentStorybook\Accessibility::check(
        ['wide' => '<p style="width: 600px">A line too wide for a phone</p>', 'scrolls' => '<div style="overflow-x: auto" tabindex="0" role="region" aria-label="Lines"><table style="width: 600px"><tr><th>Item</th></tr></table></div>'],
        ['page' => '<!doctype html><html><head></head><body><main><h1>Gadgets</h1></main></body></html>'],
    );

    if ($unchecked !== null) {
        $this->markTestSkipped($unchecked);
    }

    expect(implode("\n", $issues))
        ->toContain('`wide`: [serious] reflow')
        ->not->toContain('`scrolls`')
        ->toContain('`page`: [serious] html-has-lang')
        ->toContain('`page`: [serious] document-title');
});

it('installs into an app that meets the standard, and changing nothing on a second run', function (): void {
    $provider = "<?php\n\nnamespace App\\Providers\\Filament;\n\nuse Filament\\Panel;\n\nclass AdminPanelProvider\n{\n    public function panel(Panel \$panel): Panel\n    {\n        return \$panel\n            ->default()\n            ->id('admin')\n            ->path('admin');\n    }\n}\n";
    $installed = InstallCommand::addPlugin($provider);

    expect($installed)
        ->toContain("            ->id('admin')\n            ->plugin(\\Josephdlmd\\FilamentStorybook\\StorybookPlugin::make())\n            ->path('admin');")
        ->and(InstallCommand::addPlugin($installed))->toBe($installed);

    $boost = InstallCommand::addBoostPackage('{"agents": ["claude_code"], "packages": ["filament/filament"]}');
    expect(json_decode($boost, true)['packages'])->toBe(['filament/filament', 'josephdlmd/filament-storybook'])
        ->and(InstallCommand::addBoostPackage($boost))->toBe($boost)
        ->and(InstallCommand::addGitignoreLine("/vendor\n"))->toBe("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n")
        ->and(InstallCommand::addGitignoreLine("/vendor\n/public/css/josephdlmd\n"))->toBe("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n")
        ->and(InstallCommand::addGitignoreLine("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n"))->toBe("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n");
});

it('refuses an app that does not meet the standard, saying what to change', function (): void {
    expect(InstallCommand::unmetRequirements(boost: true, pest: true, npm: true, defaultPanel: true))->toBe([])
        ->and(InstallCommand::unmetRequirements(boost: true, pest: false, npm: true, defaultPanel: false))->toHaveCount(2)
        ->and(implode(' ', InstallCommand::unmetRequirements(boost: false, pest: false, npm: false, defaultPanel: false)))
        ->toContain('Laravel Boost')->toContain('Pest')->toContain('package.json')->toContain('default Filament panel');

    $this->artisan('storybook:install')->assertExitCode(1);
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

it('heads global search groups in sentence case where the panel keeps Filament\'s own provider', function (): void {
    $results = Filament\GlobalSearch\GlobalSearchResults::make()->category('purchase orders')->category('TINs');

    expect(app(Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider::class))->toBeInstanceOf(Josephdlmd\FilamentStorybook\GlobalSearch\SentenceCaseGlobalSearchProvider::class)
        ->and(Josephdlmd\FilamentStorybook\GlobalSearch\SentenceCaseGlobalSearchProvider::sentenceCase($results)->getCategories()->keys()->all())->toBe(['Purchase orders', 'TINs']);
});

it('flags only Title Case someone typed, never sentence case, names or acronyms', function (string $label, string $name, bool $flagged): void {
    expect(AuditPages::isTitleCase($label, $name))->toBe($flagged);
})->with([
    'a field labelled from its name' => ['Due Date', 'due_date', true],
    'a heading' => ['Customer Details', 'Customer Details', true],
    'sentence case' => ['Due date', 'due_date', false],
    'a name in an action' => ['Export to Excel', 'exportToExcel', false],
    'a name in a heading' => ['Notes for Xero', 'Notes for Xero', false],
    'an acronym' => ['TIN Details', 'TIN Details', false],
    'one word' => ['Price', 'price', false],
]);

it('skips pages the user may not open, and pages that need route parameters, instead of failing them', function (): void {
    GadgetResource::$creatable = false;

    try {
        expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
            ->toContain('0 failing')
            ->toContain('- `CreateGadget`: tester@example.com may not open it; pass "as" with a user who can.')
            ->toContain("- `ReportGadgets`: it needs route parameters the audit can't supply");
    } finally {
        GadgetResource::$creatable = true;
    }
});

it('holds amounts to centavos, unit costs to 4 decimals and quantities to 3, on the server', function (string $stories, string $field, string $typed, bool $valid): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => $stories, 'story' => 'default'])->set("data.{$field}", $typed);

    $valid
        ? expect($canvas->instance()->form->getState()[$field])->toEqual($typed)
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, 'decimal places');
})->with([
    'amount, centavos' => [Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'amount', '1250.50', true],
    'amount, a third decimal' => [Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'amount', '1.234', false],
    'record form credit limit, a third decimal' => [RecordFormStories::class, 'credit_limit', '250000.555', false],
    'unit cost, 4 decimals' => [Josephdlmd\FilamentStorybook\Stories\UnitCostStories::class, 'unit_cost', '89.2857', true],
    'unit cost, a fifth decimal' => [Josephdlmd\FilamentStorybook\Stories\UnitCostStories::class, 'unit_cost', '89.28571', false],
    'quantity, 3 decimals' => [Josephdlmd\FilamentStorybook\Stories\QuantityStories::class, 'quantity', '12.125', true],
    'quantity, a fourth decimal' => [Josephdlmd\FilamentStorybook\Stories\QuantityStories::class, 'quantity', '12.1255', false],
]);

it('words every error as GOV.UK does, even on a field that sets a message of its own', function (string $field, string $typed, string $error): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => RecordFormStories::class, 'story' => 'default'])->set("data.{$field}", $typed);

    expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, $error);
})->with([
    'an amount that is not a number' => ['credit_limit', 'abc', 'Credit limit must be a number'],
    'an amount below zero' => ['credit_limit', '-5', 'Credit limit must be 0 or more'],
    'an empty field that sets a message of its own for another rule' => ['tin', '', 'Enter TIN'],
    'a TIN in the wrong format' => ['tin', '12345', 'Enter a TIN in the correct format: 12 digits, including the branch code'],
]);

it('works out a line total as quantity × unit price, rounded once to centavos, half up', function (string $quantity, string $price, ?string $total): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\LineItemsStories::class, 'story' => 'default'])
        ->set('data.lines', [['description' => 'Gloves', 'quantity' => $quantity, 'unit_price' => $price]]);

    $total === null
        ? expect($canvas->html())->not->toContain('₱0.00')
        : expect($canvas->html())->toContain($total);
})->with([
    'rounds once' => ['2.5', '89.2857', '₱223.21'],
    'half a centavo rounds up' => ['0.5', '100.05', '₱50.03'],
    'a negative line rounds away from zero, mirroring the sale' => ['-0.5', '100.05', '-₱50.03'],
    'whole numbers' => ['12', '285.5', '₱3,426.00'],
    'a half centavo' => ['1', '0.005', '₱0.01'],
    'a tiny unit price' => ['3', '0.0001', '₱0.00'],
    'an exponent, as a number field allows' => ['1e3', '2', '₱2,000.00'],
    'no quantity yet' => ['', '2', null],
]);

it('builds the View page pattern on a real resource page: related lists in the main column, Edit on the summary', function (): void {
    $gadget = Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5]);
    $gadget->parts()->create(['name' => 'Bulb']);

    $page = Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages\ViewGadgetInLayout::class, ['record' => $gadget->getKey()])
        ->assertSee('Bulb')
        ->assertSee('Spare parts')
        ->assertSeeHtml('href="'.GadgetResource::getUrl('edit', ['record' => $gadget]).'"')
        ->assertDontSeeHtml('fi-tabs');

    expect((fn (): array => static::warnings('ViewGadgetInLayout', $page->instance()))->call(new AuditPages))->toBe([]);
});

it('shows a record\'s facts in their field patterns\' formats, with nothing invented for a missing value', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordDetailsStories::class, 'story' => 'default'])
        ->assertDontSee('Add due date')
        ->assertSee('123-456-789-000')
        ->assertSee('₱2,649.21');
});

it('keeps a locked record\'s values out of the save, even when changed in the browser', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordLockedStories::class, 'story' => 'default'])
        ->assertSeeHtml('readonly')
        ->assertSee('30 days')
        ->set('data.reference', 'CHANGED')
        ->set('data.amount', '1')
        ->set('data.notes', 'Checked by the warehouse');

    expect($canvas->instance()->form->getState())->toBe(['notes' => 'Checked by the warehouse']);
});

it('works out a document\'s VAT once per class, as 12/112 of the VAT-inclusive total, half up', function (array $lines, array $totals): void {
    expect(Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals::of($lines))->toBe($totals);
})->with([
    'VAT from a VAT-inclusive total' => [[['quantity' => '1', 'unit_price' => '112', 'vat_class' => 'vatable']], ['vatable_sales' => '100.00', 'vat' => '12.00', 'zero_rated_sales' => '0.00', 'exempt_sales' => '0.00', 'total' => '112.00']],
    'once per class, not per line' => [[['quantity' => '1', 'unit_price' => '0.05', 'vat_class' => 'vatable'], ['quantity' => '1', 'unit_price' => '0.05', 'vat_class' => 'vatable']], ['vatable_sales' => '0.09', 'vat' => '0.01', 'zero_rated_sales' => '0.00', 'exempt_sales' => '0.00', 'total' => '0.10']],
    'every class apart' => [[['quantity' => '12', 'unit_price' => '285.5', 'vat_class' => 'vatable'], ['quantity' => '2', 'unit_price' => '1450', 'vat_class' => 'exempt'], ['quantity' => '1', 'unit_price' => '500', 'vat_class' => 'zero_rated']], ['vatable_sales' => '3058.93', 'vat' => '367.07', 'zero_rated_sales' => '500.00', 'exempt_sales' => '2900.00', 'total' => '6826.00']],
    'a credit mirrors its sale' => [[['quantity' => '-1', 'unit_price' => '112', 'vat_class' => 'vatable']], ['vatable_sales' => '-100.00', 'vat' => '-12.00', 'zero_rated_sales' => '0.00', 'exempt_sales' => '0.00', 'total' => '-112.00']],
    'an unfinished line counts as nothing' => [[['quantity' => '', 'unit_price' => '50', 'vat_class' => 'vatable'], ['quantity' => '1', 'unit_price' => '50']], ['vatable_sales' => '0.00', 'vat' => '0.00', 'zero_rated_sales' => '0.00', 'exempt_sales' => '0.00', 'total' => '0.00']],
]);

it('withholds from a supplier\'s invoice once, the rate on its total less VAT, and writes amounts as on a receipt', function (): void {
    $totals = Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals::class;

    $code = fn (string $code) => Josephdlmd\FilamentStorybook\Stories\Documents\WithholdingCode::find($code);

    expect($totals::withholding('5298.00', '567.64', $code('WC158')))->toBe('47.30')
        ->and($totals::withholding('1120.00', '120.00', $code('WC160')))->toBe('20.00')
        ->and($totals::withholding('100.50', '0.00', $code('WC158')))->toBe('1.01')
        ->and($totals::withholding('5298.00', '567.64', null))->toBe('0.00')
        ->and($totals::inWords('6266.41'))->toBe('Six thousand two hundred sixty-six pesos and 41/100')
        ->and($totals::inWords('1'))->toBe('One peso and 00/100')
        // ₱6,326.00 with ₱2,900.00 exempt: the VAT is 12/112 of the other ₱3,426.00 alone.
        ->and($totals::vatGap(bcsub('6326.00', '2900.00', 2), '367.07'))->toBe('0.0014');
});

it('records a supplier\'s invoice as printed, withholding at the supplier\'s code once', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentTotalsStories::class, 'story' => 'supplierInvoice'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordSupplierInvoice')->schemaComponent('story', 'form'))
        ->assertSchemaStateSet(['withholding_code' => 'WC158', 'vat' => null], 'mountedActionSchema0')
        ->fillForm(['supplier_invoice_number' => 'BT-20931', 'supplier_invoice_date' => today()->toDateString(), 'total' => '5298.00', 'vat' => '567.64', 'is_vat_invoice' => true]);
    $schema = $canvas->instance()->getSchema('mountedActionSchema0');

    expect($schema->getComponent('tax_withheld')->getState())->toBe('47.30')
        ->and($schema->getComponent('net_payable')->getState())->toBe('5250.70');

    $canvas->callMountedAction()->assertHasNoFormErrors()->assertNotified('Supplier\'s invoice for order PO-000042 recorded');
});

it('numbers posted documents in one series per prefix, with no gaps', function (): void {
    Illuminate\Support\Facades\Schema::create('document_numbers', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->string('prefix')->unique();
        $table->unsignedInteger('last');
    });
    Illuminate\Support\Facades\DB::table('document_numbers')->insert([['prefix' => 'SI', 'last' => 0], ['prefix' => 'PO', 'last' => 41]]);
    $next = fn (string $prefix): string => Illuminate\Support\Facades\DB::transaction(fn (): string => Josephdlmd\FilamentStorybook\Stories\Documents\DocumentNumber::next($prefix));

    expect([$next('SI'), $next('SI'), $next('PO')])->toBe(['SI-000001', 'SI-000002', 'PO-000042'])
        ->and(fn () => $next('RR'))->toThrow(RuntimeException::class, 'no number series for RR')
        ->and(fn () => Josephdlmd\FilamentStorybook\Stories\Documents\DocumentNumber::next('SI'))->toThrow(RuntimeException::class, 'inside the transaction');

    // A posting that fails after taking its number gives the number back.
    rescue(fn () => Illuminate\Support\Facades\DB::transaction(function (): void {
        Josephdlmd\FilamentStorybook\Stories\Documents\DocumentNumber::next('SI');

        throw new RuntimeException('Posting failed');
    }), report: false);

    expect($next('SI'))->toBe('SI-000003');
});

it('checks a payment against the balance due, with the tax withheld', function (array $data, ?string $error): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\PaymentStories::class, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make('recordPayment')->schemaComponent('story', 'form'), ['paid_on' => today()->toDateString(), 'number' => '004518', 'tax_atc' => 'WC158', ...$data]);

    $error === null ? $canvas->assertHasNoFormErrors() : $canvas->assertHasFormErrors([in_array(array_key_first($data), ['number', 'tax_atc'], true) ? array_key_first($data) : 'amount' => $error]);
})->with([
    'a receipt serial already recorded' => [['number' => '004516', 'amount' => '1000'], 'Enter the serial of the receipt you wrote; receipt 004516 is already recorded'],
    'part of the balance' => [['amount' => '50000', 'tax_withheld' => '500', 'vat_withheld' => '2500'], null],
    'the whole balance, net of both taxes' => [['amount' => '106000.00', 'tax_withheld' => '1000.00', 'vat_withheld' => '5000.00'], null],
    'only the taxes, the cash paid earlier' => [['amount' => '0', 'tax_withheld' => '1000', 'vat_withheld' => '5000'], null],
    'more than the balance' => [['amount' => '106000.00', 'tax_withheld' => '1000.00', 'vat_withheld' => '5000.01'], 'Amount received and taxes withheld together must be ₱112,000.00 or less, the balance due'],
    'nothing' => [['amount' => '0', 'tax_withheld' => '', 'vat_withheld' => ''], 'Enter the amount received, the tax withheld, or both'],
    'income tax withheld with no ATC' => [['tax_atc' => null, 'amount' => '50000', 'tax_withheld' => '500', 'vat_withheld' => '2500'], 'Select the ATC of the income tax withheld, as on the customer\'s 2307'],
]);

it('keeps the income payment and ATC with each payment, for the SAWT', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\PaymentStories::class, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make('recordPayment')->schemaComponent('story', 'form'), ['paid_on' => today()->toDateString(), 'number' => '004519', 'amount' => '53000.00', 'tax_withheld' => '500.00', 'vat_withheld' => '2500.00', 'tax_atc' => 'WC158'])
        ->assertHasNoFormErrors();

    // Half the invoice settled: half its ₱100,000 VAT-exclusive amount.
    expect(Josephdlmd\FilamentStorybook\Stories\Documents\Payment::query()->where('number', '004519')->first(['income_payment', 'tax_atc'])->toArray())
        ->toEqual(['income_payment' => '50000.00', 'tax_atc' => 'WC158']);
});

it('picks a customer by code or name, showing both', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordPickerStories::class, 'story' => 'default'])
        ->assertSee('C-0003 Cebu Diagnostics Laboratory');

    $select = $canvas->instance()->form->getComponent('customer_id');

    expect($select->getSearchResults('C-0004'))->toBe([4 => 'C-0004 Davao Family Clinic'])
        ->and($select->getSearchResults('iloilo'))->toBe([5 => 'C-0005 Iloilo Dental Group']);
});

it('adds a customer from the picker in a slide-over and chooses it', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordPickerStories::class, 'story' => 'withAdd'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('createOption')->schemaComponent('customer_id', 'form'))
        ->setActionData(['code' => 'C-0009', 'name' => 'Zambales Rural Health Unit'])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    $id = Josephdlmd\FilamentStorybook\Stories\Documents\Customer::query()->where('code', 'C-0009')->value('id');

    expect($id)->not->toBeNull()
        ->and($canvas->get('data.customer_id'))->toEqual($id);
});

it('links to the record before and after this one in number order, leaving out the link at an end', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordNeighboursStories::class, 'story' => 'default'])
        ->assertSee('Previous invoice 001231')
        ->assertSee('Next invoice 001234');

    // The last invoice in the series: no Next link, rather than a disabled one.
    DB::connection('storybook')->beginTransaction();

    try {
        DB::connection('storybook')->table('sales_invoices')->where('number', '001234')->delete();

        Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordNeighboursStories::class, 'story' => 'default'])
            ->assertSee('Previous invoice 001231')
            ->assertDontSee('Next invoice');
    } finally {
        DB::connection('storybook')->rollBack();
    }
});

it('proposes what a government office withholds: 1% income tax on the amount less VAT and 5% VAT on the VATable sales, and the cash expected', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\PaymentStories::class, 'story' => 'default'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordPayment')->schemaComponent('story', 'form'))
        ->assertSchemaStateSet(['amount' => '106000.00', 'tax_withheld' => '1000.00', 'vat_withheld' => '5000.00'], 'mountedActionSchema0');

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals::expectedWithholding('2363.71', '3363.71', '3003.31', '0.01'))->toBe('21.10')
        // ₱3,426 VATable (₱3,058.93 before VAT) and ₱2,900 exempt: VAT is withheld on the VATable sales alone.
        ->and(Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals::expectedWithholding('6326.00', '6326.00', '3058.93', '0.05'))->toBe('152.95');
});

it('sets what a customer withholds by type, with no income tax on a government purchase of ₱10,000 or less', function (?string $type, string $total, array $rates): void {
    expect(Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals::customerRates($type, $total))->toBe($rates);
})->with([
    'government, over ₱10,000' => ['government', '10000.01', ['income_tax' => '0.01', 'vat' => '0.05']],
    'government, exactly ₱10,000' => ['government', '10000.00', ['income_tax' => '0', 'vat' => '0.05']],
    'government, a small purchase' => ['government', '2500.00', ['income_tax' => '0', 'vat' => '0.05']],
    'a top withholding agent' => ['top_withholding_agent', '2500.00', ['income_tax' => '0.01', 'vat' => '0']],
    'no withholding' => ['none', '112000.00', ['income_tax' => '0', 'vat' => '0']],
    'not set' => [null, '112000.00', ['income_tax' => '0', 'vat' => '0']],
]);

it('offers a top withholding agent\'s codes for a supplier only when the company is one', function (bool $agent, array $codes): void {
    config(['company.top_withholding_agent' => $agent]);

    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentTotalsStories::class, 'story' => 'supplierInvoice'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordSupplierInvoice')->schemaComponent('story', 'form'));

    expect(array_keys($canvas->instance()->getSchema('mountedActionSchema0')->getComponent('withholding_code')->getOptions()))->toBe($codes);
})->with([
    'a top withholding agent' => [true, ['WC158', 'WC160', 'none']],
    'any other company' => [false, ['none']],
]);

it('posts a sales invoice under the serial of its BIR form, refusing one already recorded', function (string $number, ?string $error): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentLifecycleStories::class, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make('post')->schemaComponent('story', 'form'), ['number' => $number]);

    $error === null ? $canvas->assertHasNoFormErrors() : $canvas->assertHasFormErrors(['number' => $error]);
})->with([
    'an unused serial' => ['001233', null],
    'a serial already recorded' => ['001232', 'Enter the serial of the form you wrote; invoice 001232 is already recorded'],
]);

it('voids an invoice only once its payments are void, asking the database each time, and says so instead of asking for a reason', function (): void {
    app(StoryRegistry::class)->discover(__DIR__.'/../Fixtures/Stories', 'Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories');
    $void = Filament\Actions\Testing\TestAction::make('void')->schemaComponent('story', 'form');

    $paid = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories\PaidInvoiceVoidStories::class, 'story' => 'default'])
        ->mountAction($void);
    $action = $paid->instance()->getMountedAction();

    expect($action->getModalHeading())->toBe('Invoice 001232 has payments')
        ->and($action->getModalDescription())->toStartWith('Void its payments first')
        ->and($action->getModalSubmitAction())->toBeNull()
        ->and($action->getModalCancelAction())->toBeNull();

    $paid->callMountedAction()->assertNotNotified('Invoice 001232 voided');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\VoidStories::class, 'story' => 'default'])
        ->callAction($void, ['reason' => 'Issued to the wrong customer'])
        ->assertNotified('Invoice 001234 voided');
});

it('downloads a posted document as a tagged A4 PDF in the shared layout, an invoice marked as a copy', function (): void {
    Spatie\LaravelPdf\Facades\Pdf::fake();

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DownloadPdfStories::class, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make('downloadPdf')->schemaComponent('story', 'form'))
        ->assertFileDownloaded('Sales invoice (copy) 001232.pdf');

    Spatie\LaravelPdf\Facades\Pdf::assertViewIs('filament-storybook::documents.print');
    Spatie\LaravelPdf\Facades\Pdf::assertSee(['Sales invoice (copy)', '001232', 'Cebu Diagnostics Laboratory', '₱3,426.00', '₱367.07', '₱6,326.00', 'NOT VALID FOR CLAIM OF INPUT TAX', '<html lang="en">', '<title>Sales invoice (copy) 001232</title>', '<th scope="col"']);
    $pdf = (fn (): Spatie\LaravelPdf\PdfBuilder => $this->generatedPdfs[0])->call(Spatie\LaravelPdf\Facades\Pdf::getFacadeRoot());

    expect($pdf->tagged)->toBeTrue()
        ->and((fn (): ?string => $this->format)->call($pdf))->toBe('a4');
});

it('lists a customer\'s open invoices on a statement, with each ageing bucket and the total due', function (): void {
    $customer = (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard', 'tin' => '987-654-321-000']);
    $invoice = fn (string $number, string $due, string $balance) => (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['number' => $number, 'invoice_date' => '2026-08-01', 'due_date' => $due, 'balance_due' => $balance]);
    $statement = Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::statementOfAccount($customer, [
        $invoice('001200', '2026-07-01', '100.10'),  // 152 days
        $invoice('001214', '2026-09-01', '890.50'),  // 90 days
        $invoice('001220', '2026-10-01', '10.00'),   // 60 days
        $invoice('001233', '2026-11-30', '2363.71'), // due today
        $invoice('001240', '2026-12-20', '4480.00'), // not yet due
    ], Illuminate\Support\Carbon::parse('2026-11-30'));

    expect($statement['totals'])->toBe(['Current' => '6843.71', '1–30 days' => '0.00', '31–60 days' => '10.00', '61–90 days' => '890.50', 'Over 90 days' => '100.10', 'Total due' => '7844.31'])
        ->and(array_column($statement['lines'], 'days'))->toBe([152, 90, 60, null, null])
        ->and($statement['number'])->toBe('Nov 30, 2026');
});

it('acknowledges a collection under its receipt\'s serial, not as an official receipt', function (): void {
    $payment = (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['number' => '000387', 'paid_on' => '2026-11-08', 'amount' => '6298.69', 'tax_withheld' => '27.31', 'vat_withheld' => '0.00', 'reference' => 'Check 0047731']);

    $document = Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::paymentAcknowledgement($payment);

    expect($document['totals'])->toBe(['Amount received' => '6298.69', 'Income tax withheld' => '27.31', 'Total settled' => '6326.00'])
        ->and($document['statement'])->toBe('THIS IS NOT AN OFFICIAL RECEIPT');
});

it('emails a document as its PDF from the company address, and tells the sender when it has gone or failed', function (): void {
    Spatie\LaravelPdf\Facades\Pdf::fake();
    config(['mail.default' => 'array', 'queue.default' => 'sync']);
    $sender = User::forceCreate(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secret']);
    $document = ['title' => 'Statement of account', 'number' => 'Nov 30, 2026'] + Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::statementOfAccount((new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['name' => 'Cebu Diagnostics Laboratory']), []);
    $job = new Josephdlmd\FilamentStorybook\Stories\Documents\EmailDocument($document, 'accounts@cebudiagnostics.ph', null, $sender);

    $job->handle();
    $job->failed(new RuntimeException('Mail server down'));

    $sent = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
    expect($sent->getFrom()[0]->getAddress())->toBe('billing@sample.ph')
        ->and($sent->getTo()[0]->getAddress())->toBe('accounts@cebudiagnostics.ph')
        ->and($sent->getSubject())->toStartWith('Statement of account')
        ->and($sent->getTextBody())->toStartWith('Attached is Statement of account')
        ->and($sent->getAttachments()[0]->getFilename())->toEndWith('.pdf')
        ->and($sender->notifications()->pluck('data')->pluck('title')->all())->toEqualCanonicalizing([
            'Statement of account Nov 30, 2026 emailed to accounts@cebudiagnostics.ph',
            'Statement of account Nov 30, 2026 not emailed',
        ]);
});

it('claims customer withholding only once its 2307 is in, a quarter at a time', function (): void {
    $CustomerWithholding = Josephdlmd\FilamentStorybook\Stories\Reports\CustomerWithholding::class;
    $sawt = fn (int $year, int $quarter): array => $CustomerWithholding::sawt($year, $quarter)->orderBy('customer')->get()
        ->map(fn ($row): array => [$row->tin, $row->customer, $row->tax_atc, number_format((float) $row->income_payment, 2, '.', ''), number_format((float) $row->tax_withheld, 2, '.', ''), number_format((float) $row->vat_withheld, 2, '.', '')])->all();

    // Still waiting, oldest first: Abra's October payment and Cebu's November one.
    expect($CustomerWithholding::awaiting()->pluck('number')->all())->toBe(['004532', '004516'])
        // October to December: only Cebu's, whose 2307 arrived; the void payment and the ones still waiting are left out.
        ->and($sawt(2026, 4))->toBe([['987-654-321-000', 'Cebu Diagnostics Laboratory', 'WC158', '3003.31', '30.03', '150.17']])
        ->and($sawt(2026, 3))->toBe([['234-567-890-000', 'Abra Medical Supply', 'WC158', '4464.00', '44.64', '0.00']])
        ->and($sawt(2026, 1))->toBe([]);

    $this->travelTo('2026-10-12');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\CustomerWithholdingStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertSee(['Cebu Diagnostics Laboratory', '₱150.17'])
        ->assertDontSee('Abra Medical Supply')
        ->filterTable('quarter', ['year' => '2026', 'quarter' => '3'])
        ->assertSee(['Abra Medical Supply', '₱44.64'])
        ->assertDontSee('Cebu Diagnostics Laboratory');
});

it('marks a customer\'s 2307 received on a date no later than today', function (): void {
    $this->travelTo('2026-10-12');
    $action = Filament\Actions\Testing\TestAction::make('markForm2307Received')->schemaComponent('story', 'form');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\Form2307ReceivedStories::class, 'story' => 'default'])
        ->callAction($action, ['received_2307_on' => null])
        ->assertHasFormErrors(['received_2307_on' => 'required'])
        ->callAction($action, ['received_2307_on' => '2026-10-13'])
        ->assertHasFormErrors(['received_2307_on' => 'before_or_equal'])
        ->callAction($action, ['received_2307_on' => '2026-10-12'])
        ->assertHasNoFormErrors()
        ->assertNotified('2307 for receipt 004516 marked received');
});

it('totals a report over every row its filters keep, and prints it with the filters as its details', function (): void {
    Spatie\LaravelPdf\Facades\Pdf::fake();

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\SalesReportStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertSee('₱26,780.21')
        ->filterTable('invoice_date', ['from' => '2026-10-01'])
        ->assertDontSee('₱26,780.21')
        ->assertSee('₱9,689.71')
        ->callAction(Filament\Actions\Testing\TestAction::make('downloadPdf')->table())
        ->assertFileDownloaded('Sales report '.today()->translatedFormat('M j, Y').'.pdf');

    Spatie\LaravelPdf\Facades\Pdf::assertSee(['Sales report', 'Period', 'From Oct 1, 2026', '001232', 'Oct 11, 2026', '₱9,689.71']);
});

it('shows a report\'s From and To dates as separate indicators, so each clears on its own', function (string $stories, string $filter): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => $stories, 'story' => 'default'])
        ->call('loadTable')
        ->filterTable($filter, ['from' => '2026-10-01', 'until' => '2026-10-31']);

    expect(collect($canvas->instance()->getTable()->getFilter($filter)->getIndicators())->map->getLabel()->all())
        ->toBe(['from' => 'From Oct 1, 2026', 'until' => 'Up to Oct 31, 2026']);
})->with([
    'sales' => [Josephdlmd\FilamentStorybook\Stories\SalesReportStories::class, 'invoice_date'],
    'purchases' => [Josephdlmd\FilamentStorybook\Stories\PurchasesReportStories::class, 'order_date'],
    'collections' => [Josephdlmd\FilamentStorybook\Stories\CollectionsReportStories::class, 'paid_on'],
]);

it('downloads every report as a PDF of the rows its filters keep', function (string $stories, string $story, string $title, array $sees): void {
    Spatie\LaravelPdf\Facades\Pdf::fake();

    Livewire::test(StoryCanvas::class, ['stories' => $stories, 'story' => $story])
        ->call('loadTable')
        ->callAction(Filament\Actions\Testing\TestAction::make('downloadPdf')->table())
        ->assertFileDownloaded($title.' '.today()->translatedFormat('M j, Y').'.pdf');

    Spatie\LaravelPdf\Facades\Pdf::assertSee([$title, ...$sees]);
})->with([
    'collections' => [Josephdlmd\FilamentStorybook\Stories\CollectionsReportStories::class, 'default', 'Collections report', ['004516', '₱6,298.69', 'All dates']],
    'ageing' => [Josephdlmd\FilamentStorybook\Stories\ReceivablesAgeingStories::class, 'default', 'Receivables ageing', ['As of', 'Abra Medical Supply', 'Total due']],
    'purchases' => [Josephdlmd\FilamentStorybook\Stories\PurchasesReportStories::class, 'default', 'Purchases report', ['PO-000043', '₱14,000.00']],
    'payables' => [Josephdlmd\FilamentStorybook\Stories\PurchasesReportStories::class, 'payables', 'Payables', ['PO-000042', 'Days past due', '₱12,298.00']],
]);

it('counts days past due from the day after the due date, in the report and on the statement alike', function (): void {
    $days = Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::daysPastDue(...);
    $asOf = Illuminate\Support\Carbon::parse('2026-10-14');

    expect($days('2026-10-15', $asOf))->toBeNull()
        ->and($days('2026-10-14', $asOf))->toBeNull()
        ->and($days('2026-10-13', $asOf))->toBe(1)
        ->and($days(null, $asOf))->toBeNull();
});

it('ages each customer\'s open balance by days past due, as of a date', function (): void {
    $this->travelTo('2026-10-14');
    $abra = Josephdlmd\FilamentStorybook\Stories\Documents\Customer::where('code', 'C-0001')->sole();

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\ReceivablesAgeingStories::class, 'story' => 'default'])
        ->call('loadTable')
        // Abra: ₱5,000 is 105 days past due and ₱890.50 is 25; Bicol and Cebu aren't due yet.
        ->assertTableColumnStateSet('over_90', '5000.00', $abra)
        ->assertTableColumnStateSet('days_1_30', '890.50', $abra)
        ->assertSee(['₱14,563.71', '₱20,454.21'])
        // By Dec 1 nothing is current any more; the total due is the same.
        ->filterTable('as_of', ['as_of' => '2026-12-01'])
        ->assertDontSee('₱14,563.71')
        ->assertSee(['₱3,363.71', '₱11,200.00', '₱20,454.21'])
        // On Sep 1, Cebu's invoice of Sep 25 wasn't issued yet.
        ->filterTable('as_of', ['as_of' => '2026-09-01'])
        ->assertDontSee('₱11,200.00')
        ->assertTableColumnStateSet('days_61_90', '5000.00', $abra);
});

it('copies the lines of one document into the next, with their prices and only what is left', function (): void {
    $F = Josephdlmd\FilamentStorybook\Stories\Documents\Fulfilment::class;
    $lines = [
        ['id' => 1, 'description' => 'Gloves', 'vat_class' => 'vatable', 'quantity' => '100', 'delivered' => '40', 'unit_price' => '285.50'],
        ['id' => 2, 'description' => 'Pens', 'vat_class' => 'exempt', 'quantity' => '20', 'delivered' => '20', 'unit_price' => '1450.00'],
    ];

    expect($F::remaining('12.5', '0.25'))->toBe('12.250')
        ->and($F::status($lines))->toBe('Partly delivered')
        ->and($F::status([['quantity' => '5', 'delivered' => '0']]))->toBe('Not delivered')
        ->and($F::status([['quantity' => '5', 'delivered' => '5']]))->toBe('Delivered')
        ->and($F::nextLines($lines, [1 => '60', 2 => '0']))->toBe([
            ['source_line_id' => 1, 'description' => 'Gloves', 'vat_class' => 'vatable', 'unit_price' => '285.50', 'quantity' => '60'],
        ]);
});

it('creates a delivery of what is left on an order, refusing more', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\NextDocumentStories::class, 'story' => 'default'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('createDelivery')->schemaComponent('story', 'form'));
    // The delivered pens are left out; the gloves and the alcohol start at what is left.
    [$gloves, $alcohol] = array_keys($canvas->get('mountedActions.0.data.lines'));

    expect(array_column($canvas->get('mountedActions.0.data.lines'), 'quantity'))->toEqual([60, 12.5]);

    $canvas->fillForm(["lines.{$gloves}.quantity" => '61'])
        ->callMountedAction()
        ->assertHasFormErrors(["lines.{$gloves}.quantity" => 'max'])
        ->fillForm(["lines.{$gloves}.quantity" => '60', "lines.{$alcohol}.quantity" => '0'])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified('Delivery created for order SO-000012');
});

it('keeps stock at weighted average cost and never below zero', function (): void {
    $Stock = Josephdlmd\FilamentStorybook\Stories\Documents\Stock::class;
    $gloves = fn () => Josephdlmd\FilamentStorybook\Stories\Documents\Item::where('code', 'I-0001')->sole();
    $db = Illuminate\Support\Facades\DB::connection($gloves()->getConnectionName());

    expect(fn () => $Stock::issue($gloves(), '1', 'Delivery DR-000040'))->toThrow(RuntimeException::class, 'inside the transaction');

    // 140 at ₱212.50 and 60 at ₱220.00: (29,750 + 13,200) ÷ 200 = ₱214.75.
    $db->transaction(fn () => $Stock::receive($gloves(), '60', '220.0000', 'Receiving report RR-000008'));
    expect([$gloves()->on_hand, $gloves()->average_cost])->toBe(['200.000', '214.7500']);

    // Issues and adjustments go out at the average, which they don't move.
    $db->transaction(fn () => $Stock::issue($gloves(), '150.5', 'Delivery DR-000041'));
    expect([$gloves()->on_hand, $gloves()->average_cost])->toBe(['49.500', '214.7500'])
        ->and($Stock::isLow($gloves()))->toBeTrue()
        ->and($gloves()->stockMovements()->latest('id')->first()->only('quantity', 'unit_cost', 'on_hand'))->toBe(['quantity' => '-150.500', 'unit_cost' => '214.7500', 'on_hand' => '49.500']);

    expect(fn () => $db->transaction(fn () => $Stock::adjust($gloves(), '-50', 'Adjustment ADJ-000004')))
        ->toThrow(RuntimeException::class, 'Only 49.500 boxes of Nitrile examination gloves, medium on hand');
    expect($gloves()->on_hand)->toBe('49.500');
});

it('sends a stock adjustment for approval with a reason, never a change of 0', function (): void {
    $action = Filament\Actions\Testing\TestAction::make('adjustStock')->schemaComponent('story', 'form');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\AdjustStockStories::class, 'story' => 'default'])
        ->callAction($action, ['change' => '0'])
        ->assertHasFormErrors(['change' => 'Enter a change other than 0', 'reason' => 'required'])
        ->callAction($action, ['change' => '-0.000', 'reason' => 'damaged'])
        ->assertHasFormErrors(['change' => 'Enter a change other than 0'])
        ->callAction($action, ['change' => '-2', 'reason' => 'damaged'])
        ->assertHasNoFormErrors()
        ->assertNotified('Adjustment of Insulin pens, 3 mL sent for approval');
});

it('lists what is owed to a supplier in the receivables ageing buckets', function (): void {
    $order = fn (string $number, string $due, string $balance) => (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['number' => $number, 'order_date' => '2026-06-01', 'due_date' => $due, 'balance_due' => $balance]);
    $statement = Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::supplierStatement('Luzon Medical Gases', [
        $order('PO-000040', '2026-07-01', '100.00'),  // 103 days
        $order('PO-000043', '2026-09-14', '7000.00'), // 28 days
        $order('PO-000046', '2026-10-31', '3360.00'), // not yet due
    ], Illuminate\Support\Carbon::parse('2026-10-12'));

    expect($statement['title'])->toBe('Supplier statement')
        ->and($statement['party'])->toBe(['label' => 'Supplier', 'name' => 'Luzon Medical Gases'])
        ->and($statement['columns']['number']['label'])->toBe('Order')
        ->and(array_column($statement['lines'], 'days'))->toBe([103, 28, null])
        ->and($statement['totals'])->toBe(['Current' => '3360.00', '1–30 days' => '7000.00', '31–60 days' => '0.00', '61–90 days' => '0.00', 'Over 90 days' => '100.00', 'Total due' => '10460.00']);
});

it('downloads a supplier\'s statement from their open purchase orders', function (): void {
    Spatie\LaravelPdf\Facades\Pdf::fake();

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\SupplierStatementStories::class, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make('downloadStatement')->schemaComponent('story', 'form'))
        ->assertFileDownloaded('Supplier statement '.today()->translatedFormat('M j, Y').'.pdf');

    // Benguet's PO-000041 is paid, so only PO-000042 is open.
    Spatie\LaravelPdf\Facades\Pdf::assertSee(['Supplier statement', 'Supplier', 'Benguet Trading', 'PO-000042', '₱5,298.00', 'Total due']);
});

it('counts unpaid invoices, uninvoiced orders and this order toward a customer\'s credit limit', function (): void {
    $CreditLimit = Josephdlmd\FilamentStorybook\Stories\Documents\CreditLimit::class;
    $abra = Josephdlmd\FilamentStorybook\Stories\Documents\Customer::where('code', 'C-0001')->sole();
    $bicol = Josephdlmd\FilamentStorybook\Stories\Documents\Customer::where('code', 'C-0002')->sole();

    // Unpaid invoices ₱5,890.50 and order SO-000011 not yet invoiced, ₱200,000.00, against a ₱250,000.00 limit.
    expect($CreditLimit::exposure($abra, '56509.50'))->toBe('262400.00')
        ->and($CreditLimit::over($abra, '56509.50'))->toBe('12400.00')
        ->and($CreditLimit::over($abra, '44109.50'))->toBeNull()
        ->and($CreditLimit::reason($abra, '12400.00'))->toBe("₱12,400.00 over Abra Medical Supply's ₱250,000.00 credit limit")
        // No limit set: never held.
        ->and($CreditLimit::over($bicol, '9999999.00'))->toBeNull();
});

it('holds an order over the credit limit: says by how much and sends it for approval', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\CreditLimitStories::class, 'story' => 'default'])
        ->assertSee('This order would take Abra Medical Supply ₱12,400.00 over its ₱250,000.00 credit limit', false);

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\CreditLimitStories::class, 'story' => 'sendForApproval'])
        ->callAction(Filament\Actions\Testing\TestAction::make('sendForApproval')->schemaComponent('story', 'form'))
        ->assertNotified('Order sent for approval');
});

it('groups the tax withheld from each supplier by quarter and month, as Form 2307 lists it', function (): void {
    $rows = Josephdlmd\FilamentStorybook\Stories\Reports\SupplierWithholding::quarterQuery(Josephdlmd\FilamentStorybook\Stories\Documents\PurchaseOrder::query(), '2026-3')
        ->where('supplier', 'Benguet Trading')
        ->get();

    // PO-000039 in July and PO-000041 in September; PO-000042, recorded in October, is the next quarter's.
    $document = Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::form2307Details('Benguet Trading', '234-567-890-000', '8 Session Road, Baguio City 2600', Josephdlmd\FilamentStorybook\Stories\Reports\SupplierWithholding::start('2026-3'), $rows);

    expect($document['number'])->toBe('Jul 1, 2026 to Sep 30, 2026')
        ->and($document['lines'])->toBe([['atc' => 'WC158', 'month_1' => '2232.14', 'month_2' => '0.00', 'month_3' => '4730.36', 'income_payment' => '6962.50', 'tax_withheld' => '69.62']])
        ->and($document['totals'])->toBe(['Total income payments' => '6962.50', 'Total tax withheld' => '69.62']);
});

it('reports the quarter just ended and downloads each supplier\'s 2307 details, the report and the QAP', function (): void {
    $this->travelTo('2026-10-12');
    Spatie\LaravelPdf\Facades\Pdf::fake();

    $report = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\SupplierWithholdingStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertSee(['Benguet Trading', 'Luzon Medical Gases', '₱19,462.50', '₱194.62'])
        ->assertActionVisible(Filament\Actions\Testing\TestAction::make('export')->table())
        // Benguet's row is keyed by its first order, PO-000041.
        ->callAction(Filament\Actions\Testing\TestAction::make('download2307')->table(1))
        ->assertFileDownloaded('Form 2307 details Jul 1, 2026 to Sep 30, 2026.pdf');

    Spatie\LaravelPdf\Facades\Pdf::assertSee(['Form 2307 details', 'Benguet Trading', '234-567-890-000', '₱2,232.14', '₱4,730.36', '₱69.62', 'not the certificate']);

    // A fresh fake, since assertSee looks in every PDF made so far.
    Spatie\LaravelPdf\Facades\Pdf::fake();
    $report->callAction(Filament\Actions\Testing\TestAction::make('downloadPdf')->table())
        ->assertFileDownloaded('Tax withheld from suppliers Oct 12, 2026.pdf');

    Spatie\LaravelPdf\Facades\Pdf::assertSee(['Tax withheld from suppliers', 'Jul–Sep 2026', 'Luzon Medical Gases', '₱19,462.50']);

    expect(collect(Josephdlmd\FilamentStorybook\Stories\Reports\QapExporter::getColumns())->map->getName()->all())
        ->toBe(['supplier_tin', 'supplier', 'withholding_code', 'withholdingCode.rate', 'income_payment', 'tax_withheld']);
});

it('leaves a missing date off the printed copy instead of printing today', function (): void {
    $invoice = (new Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice)->forceFill(['number' => '001232', 'invoice_date' => '2026-10-11', 'terms' => 'Cash', 'due_date' => null]);

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument::salesInvoice($invoice)['details'])
        ->toBe(['Date' => 'Oct 11, 2026', 'Terms' => 'Cash']);
});

it('refuses a serial another user saved after the form was checked, keeping the form open and saying what to check', function (string $stories, string $action, string $model, array $data, string $title): void {
    // Another user saves the same serial between this form's check and its save; only the unique index can catch that.
    $model::saving(fn (Illuminate\Database\Eloquent\Model $saved) => Illuminate\Support\Facades\DB::connection('storybook')->table($saved->getTable())->insert(
        $saved->getTable() === 'payments' ? ['sales_invoice_id' => 3, 'number' => $saved->number, 'status' => 'posted'] : ['number' => $saved->number, 'status' => 'posted'],
    ));

    Livewire::test(StoryCanvas::class, ['stories' => $stories, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make($action)->schemaComponent('story', 'form'), $data)
        ->assertNotified($title)
        ->assertActionMounted(Filament\Actions\Testing\TestAction::make($action)->schemaComponent('story', 'form'));
})->with([
    'a receipt' => [Josephdlmd\FilamentStorybook\Stories\PaymentStories::class, 'recordPayment', Josephdlmd\FilamentStorybook\Stories\Documents\Payment::class, ['paid_on' => '2026-10-11', 'number' => '004520', 'amount' => '1000', 'tax_withheld' => '0', 'vat_withheld' => '0'], 'Receipt 004520 not recorded'],
    'an invoice' => [Josephdlmd\FilamentStorybook\Stories\DocumentLifecycleStories::class, 'post', Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice::class, ['number' => '001240'], 'Invoice 001240 not posted'],
]);

it('reads the theme from Vite\'s dev server while `npm run dev` runs, then deletes its copy', function (): void {
    file_put_contents(public_path('hot'), 'http://dev.test:5173');
    Josephdlmd\FilamentStorybook\Pages\Storybook::panel()->viteTheme('resources/css/theme.css');
    Illuminate\Support\Facades\Http::fake(['dev.test:5173/*' => Illuminate\Support\Facades\Http::response('.fi-btn { min-height: 2rem }')]);
    Illuminate\Support\Facades\Process::fake(['*' => Illuminate\Support\Facades\Process::result('{}')]);

    try {
        Josephdlmd\FilamentStorybook\Accessibility::check(['story' => '<p>Text</p>']);
    } finally {
        unlink(public_path('hot'));
    }

    // `?direct` gets the CSS itself; without it Vite answers with a script that loads it.
    Illuminate\Support\Facades\Http::assertSent(fn ($request): bool => $request->url() === 'http://dev.test:5173/resources/css/theme.css?direct');
    Illuminate\Support\Facades\Process::assertRan(function ($process): bool {
        $theme = collect(json_decode($process->input, true)['css'])->first(fn (string $path): bool => str_contains($path, 'fs-theme-'));

        return $theme !== null && ! file_exists($theme);
    });
});

it('signs stock moving in, and keeps the number format', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\StockStories::class, 'story' => 'movements'])
        ->call('loadTable')
        ->assertSeeInOrder(['+100', '-40', '+80'])
        ->assertDontSee('+100.000');
});

it('lists a document\'s history oldest first, each time with its zone', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordHistoryStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertSeeInOrder(['Created', 'Posted', 'Voided', 'Issued to the wrong customer'])
        ->assertSee('Oct 10, 2026 2:05 PM UTC');
});

it('archives, restores and deletes many records, deleting only those never used', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\ArchiveStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertActionVisible(TestAction::make('archive')->table()->bulk())
        ->assertActionVisible(TestAction::make('forceDelete')->table()->bulk())
        ->assertActionHidden(TestAction::make('restore')->table()->bulk())
        ->selectTableRecords([1, 2])
        ->callAction(TestAction::make('archive')->table()->bulk())
        ->assertNotified('Archived')
        ->selectTableRecords([1, 2])
        ->callAction(TestAction::make('forceDelete')->table()->bulk())
        ->assertNotified('Deleted 1 of 2');

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\Customer::withTrashed()->whereIn('id', [1, 2])->pluck('code')->all())->toBe(['C-0001']);
});

it('narrows the chart to the dashboard filter\'s months', function (): void {
    $chart = Livewire::test(Josephdlmd\FilamentStorybook\Stories\Widgets\OrdersPerMonthChart::class, ['pageFilters' => ['from' => '2026-06-15', 'until' => '2026-08-31']]);

    expect($chart->instance()->getChartAssistiveContent())->toBe('Approved orders per month: June 38, July 51, August 47.');
});

it('names a report\'s export after the report and the day, as its PDF is', function (string $exporter, string $name): void {
    $export = new Filament\Actions\Exports\Models\Export;
    $export->created_at = Illuminate\Support\Carbon::parse('2026-10-13 09:00');

    expect((new $exporter($export, [], []))->getFileName($export))->toBe($name);
})->with([
    'sales' => [Josephdlmd\FilamentStorybook\Stories\Reports\SalesReportExporter::class, 'Sales report Oct 13, 2026'],
    'QAP' => [Josephdlmd\FilamentStorybook\Stories\Reports\QapExporter::class, 'QAP Oct 13, 2026'],
    'SAWT' => [Josephdlmd\FilamentStorybook\Stories\Reports\SawtExporter::class, 'SAWT Oct 13, 2026'],
]);

it('ignores spaces pasted before or after an amount', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'story' => 'default'])->set('data.amount', ' 1250.50 ');

    // numeric() hands the model a number, as before.
    expect($canvas->instance()->form->getState())->toBe(['amount' => 1250.5]);
});

it('lists every document status, each from the enum', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentLifecycleStories::class, 'story' => 'status'])
        ->call('loadTable')
        ->assertSeeInOrder(['Draft', 'For approval', 'Posted', 'Void']);
});

it('asks a bulk change for its one value, and exports only the ticked rows', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\BulkActionStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->selectTableRecords([1, 2])
        ->mountAction(TestAction::make('changePaymentTerms')->table()->bulk())
        ->callMountedAction()
        ->assertHasFormErrors(['payment_terms' => 'required'])
        ->fillForm(['payment_terms' => 'net_60'])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified('Payment terms changed')
        ->assertActionVisible(TestAction::make('export')->table()->bulk())
        ->assertSeeInOrder(['Put on hold', 'Take off hold', 'Change payment terms', 'Export selected', 'Delete selected']);
});
