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
use Josephdlmd\FilamentStorybook\Stories\TinInputStories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => 'secret']));
});

it('discovers the shipped stories, sorted by title', function (): void {
    expect(array_keys(app(StoryRegistry::class)->all()))
        ->toBe(['actions-approve-and-reject', 'actions-delete', 'actions-download-pdf', 'actions-record-payment', 'actions-void', 'dashboards-key-figures-and-charts', 'feedback-notifications', 'forms-choice', 'forms-date', 'forms-document-totals', 'forms-file-upload', 'forms-form-container', 'forms-line-items', 'forms-money-input', 'forms-quantity', 'forms-record-form', 'forms-record-picker', 'forms-tin', 'forms-unit-cost', 'forms-yes-or-no', 'records-details', 'records-document-lifecycle', 'records-locked', 'records-paper-receipt', 'records-related-records', 'records-view-page', 'tables-import-and-export', 'tables-list-table', 'tables-money-column', 'tables-status-badge']);
});

it('describes a story the way a components manifest does', function (): void {
    $manifest = app(StoryRegistry::class)->manifest(app(MoneyInputStories::class));

    expect($manifest['imports'])->toBe(['Filament\Forms\Components\TextInput'])
        ->and($manifest['stories'][0])->toMatchArray([
            'name' => 'default',
            'label' => 'Default',
            'kind' => 'component',
            'snippet' => "TextInput::make('amount')\n    ->numeric()\n    ->prefix('₱')\n    ->step(0.01)\n    ->minValue(0)\n    ->rules(['decimal:0,2'])\n    ->maxWidth('xs')\n    ->validationMessages(['decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50'])",
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

    expect($api->keys()->all())->toBe(['TextColumn::make', 'TextColumn::badge', 'TextColumn::formatStateUsing', 'TextColumn::color'])
        ->and($api['TextColumn::badge'])->toBe('badge(Closure|bool $condition = true): static');

    expect((string) (new ShowStories)->handle(new Request(['id' => 'tables-status-badge']))->content())
        ->toContain("## API")
        ->toContain('TextColumn::badge(Closure|bool $condition = true): static');
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
        ->assertSee('with 000 for the head office');
});

it('loads the house CSS the patterns depend on, on every panel page', function (): void {
    expect(collect(Filament\Support\Facades\FilamentAsset::getStyles(['josephdlmd/filament-storybook']))->map->getId()->all())->toBe(['house']);
});

it('corrects Filament defaults that break a sourced rule, app-wide', function (): void {
    $delete = Filament\Actions\DeleteAction::make();

    expect($delete->getColor())->toBe('danger')
        ->and($delete->isOutlined())->toBeTrue()
        ->and($delete->getModalSubmitAction()->getColor())->toBe('danger')
        ->and($delete->getModalSubmitAction()->isOutlined())->toBeFalse()
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::requiredMessage(Filament\Forms\Components\TextInput::make('name')))->toBe('Enter name')
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::requiredMessage(Filament\Forms\Components\TextInput::make('tin')->label('TIN')))->toBe('Enter TIN')
        ->and(Josephdlmd\FilamentStorybook\StorybookPlugin::requiredMessage(Filament\Forms\Components\Select::make('payment_terms')))->toBe('Select payment terms');
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
        ->toContain('Valid ids: actions-approve-and-reject, actions-delete, actions-download-pdf, actions-record-payment, actions-void, dashboards-key-figures-and-charts, feedback-notifications, forms-choice, forms-date, forms-document-totals, forms-file-upload, forms-form-container, forms-line-items, forms-money-input, forms-quantity, forms-record-form, forms-record-picker, forms-tin, forms-unit-cost, forms-yes-or-no, records-details, records-document-lifecycle, records-locked, records-paper-receipt, records-related-records, records-view-page, tables-import-and-export, tables-list-table, tables-money-column, tables-status-badge');
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
        $table->timestamps();
    });

    $import = new Josephdlmd\FilamentStorybook\Stories\Imports\SupplierImporter(new Filament\Actions\Imports\Models\Import, ['name' => 'name', 'tin' => 'tin', 'credit_limit' => 'credit_limit'], []);

    $import(['name' => 'Abra Medical Supply', 'tin' => '123 456 789 000', 'credit_limit' => '250000.50']);
    // The same TIN, typed another way, updates that supplier.
    $import(['name' => 'Abra Medical Supply Inc.', 'tin' => '123.456.789.000', 'credit_limit' => '250000.50']);

    // The amount reaches the model as typed, never a float (SQLite itself reads decimals back as floats).
    expect(Josephdlmd\FilamentStorybook\Stories\Imports\Supplier::query()->get(['name', 'tin'])->toArray())
        ->toBe([['name' => 'Abra Medical Supply Inc.', 'tin' => '123-456-789-000']])
        ->and($import->getData()['credit_limit'])->toBe('250000.50')
        ->and(fn () => $import(['name' => 'Benguet Trading', 'tin' => '123-456-789', 'credit_limit' => '1']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the TIN as 12 digits, such as 123-456-789-000')
        ->and(fn () => $import(['name' => 'Benguet Trading', 'tin' => '987-654-321-000', 'credit_limit' => '1.234']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the credit limit with at most 2 decimal places');

    expect((string) (new ShowStories)->handle(new Request(['id' => 'tables-import-and-export']))->content())
        ->toContain('## SupplierImporter')
        ->toContain('public function resolveRecord(): Supplier');
});

it('reports every story passing', function (): void {
    expect((string) (new TestStories)->handle(new Request)->content())
        ->toStartWith('# 55 passing, 0 failing, 0 warnings, 0 accessibility issues')
        ->toContain('- `forms-money-input.required`');
});

it('names every line-item field under Filament\'s own CSS, which hides the cell labels once the table shows headers', function (): void {
    // The tests have no published theme; Filament's compiled one is what apps load.
    $theme = public_path('css/filament/filament/app.css');
    @mkdir(dirname($theme), recursive: true);
    copy(__DIR__.'/../../vendor/filament/filament/dist/theme.css', $theme);

    try {
        expect((string) (new TestStories)->handle(new Request(['ids' => ['forms-line-items']]))->content())
            ->toStartWith('# 2 passing, 0 failing, 0 warnings, 0 accessibility issues');
    } finally {
        unlink($theme);
    }
});

it('reports a failing story with the line to fix, so the agent can self-correct', function (): void {
    app(StoryRegistry::class)->discover(__DIR__.'/../Fixtures/Stories', 'Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories');

    expect((string) (new TestStories)->handle(new Request(['ids' => ['broken-story']]))->content())
        ->toStartWith('# 5 passing, 3 failing, 11 warnings, 1 accessibility issues')
        ->toContain('- `broken-story.nonNullableRecord`: `fn (Model $record)` gets null while the table has no rows')
        ->toContain('- `broken-story.vagueAndTitleCase`: "Lorem ipsum" is placeholder content')
        ->toContain('- `broken-story.vagueAndTitleCase`: a button labelled "Submit"')
        ->toContain('- `broken-story.vagueAndTitleCase`: "Save Order" is in Title Case')
        ->toContain('- `broken-story.vagueAndTitleCase`: a notification title has no full stop')
        ->toContain('- `broken-story.unnamedIconButton`: [critical] button-name: Buttons must have discernible text')
        ->toContain('- `broken-story`: add description()')
        ->toContain('- `broken-story.works`: no placeholder()')
        ->toContain('- `broken-story.floatMoney`: round() works on floats')
        ->toContain('- `broken-story.throws`: add a one-line docblock')
        ->toContain('- `broken-story.failsWhileRendering`: Error: Call to a member function upper() on string at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:44')
        ->toContain('- `broken-story.throws`: RuntimeException: Story exploded at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:28')
        ->toContain('- `broken-story.untyped`: Not a story: return a Filament component, table column or action, or take and return a Table or Schema, at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:31')
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
        ->toContain('Run `storybook-test`');
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
        ->toStartWith('# 2 pages rendered, 0 failing, 25 warnings')
        ->toContain('Signed in as tester@example.com.')
        ->toContain('- `ListGadgets`: the money column `price` must be right-aligned')
        ->toContain('- `ListGadgets`: 3 row actions are inline; keep at most two')
        ->toContain('- `ListGadgets`: several filters are hidden in a dropdown')
        ->toContain('- `ListGadgets`: the empty list gives no next step')
        ->toContain('- `CreateGadget`: the field `code` uses placeholder()')
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
        ->toContain('- `OrdersPerMonthChart`')
        ->not->toContain('- `OrdersPerMonthChart`:');
});

it('audits edit pages against an existing record', function (): void {
    Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5]);

    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->toStartWith('# 6 pages rendered, 0 failing, 44 warnings')
        // Related lists are audited too, read from the resource, since a View page may embed them itself.
        ->toContain('- `PartsRelationManager`: the column "Created At" is in Title Case')
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

it('installs into an app that meets the standard, and changing nothing on a second run', function (): void {
    $provider = "<?php\n\nnamespace App\\Providers\\Filament;\n\nuse Filament\\Panel;\n\nclass AdminPanelProvider\n{\n    public function panel(Panel \$panel): Panel\n    {\n        return \$panel\n            ->default()\n            ->id('admin')\n            ->path('admin');\n    }\n}\n";
    $installed = InstallCommand::addPlugin($provider);

    expect($installed)
        ->toContain("            ->id('admin')\n            ->plugin(StorybookPlugin::make())\n            ->path('admin');")
        ->toContain("use Josephdlmd\\FilamentStorybook\\StorybookPlugin;")
        ->and(InstallCommand::addPlugin($installed))->toBe($installed);

    $boost = InstallCommand::addBoostPackage('{"agents": ["claude_code"], "packages": ["filament/filament"]}');
    expect(json_decode($boost, true)['packages'])->toBe(['filament/filament', 'josephdlmd/filament-storybook'])
        ->and(InstallCommand::addBoostPackage($boost))->toBe($boost)
        ->and(InstallCommand::addGitignoreLine("/vendor\n"))->toBe("/vendor\n/public/css/josephdlmd\n")
        ->and(InstallCommand::addGitignoreLine("/vendor\n/public/css/josephdlmd\n"))->toBe("/vendor\n/public/css/josephdlmd\n");
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
    'unit cost, 4 decimals' => [Josephdlmd\FilamentStorybook\Stories\UnitCostStories::class, 'unit_cost', '89.2857', true],
    'unit cost, a fifth decimal' => [Josephdlmd\FilamentStorybook\Stories\UnitCostStories::class, 'unit_cost', '89.28571', false],
    'quantity, 3 decimals' => [Josephdlmd\FilamentStorybook\Stories\QuantityStories::class, 'quantity', '12.125', true],
    'quantity, a fourth decimal' => [Josephdlmd\FilamentStorybook\Stories\QuantityStories::class, 'quantity', '12.1255', false],
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
        ->assertSee('₱2,363.71');
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

    expect($totals::withholding('5298.00', '567.64', '0.01'))->toBe('47.30')
        ->and($totals::withholding('1120.00', '120.00', '0.02'))->toBe('20.00')
        ->and($totals::withholding('100.50', '0.00', '0.01'))->toBe('1.01')
        ->and($totals::inWords('6266.41'))->toBe('Six thousand two hundred sixty-six pesos and 41/100')
        ->and($totals::inWords('1'))->toBe('One peso and 00/100');
});

it('records a supplier\'s invoice as printed, withholding at the supplier\'s code once', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentTotalsStories::class, 'story' => 'supplierInvoice'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordSupplierInvoice')->schemaComponent('story', 'form'))
        ->assertSchemaStateSet(['withholding_code' => 'WC158', 'vat' => null], 'mountedActionSchema0')
        ->fillForm(['supplier_invoice_number' => 'BT-20931', 'supplier_invoice_date' => today()->toDateString(), 'total' => '5298.00', 'vat' => '567.64']);
    $schema = $canvas->instance()->getSchema('mountedActionSchema0');

    expect($schema->getComponent('tax_withheld')->getState())->toBe('47.30')
        ->and($schema->getComponent('net_payable')->getState())->toBe('5250.70');

    $canvas->callMountedAction()->assertHasNoActionErrors()->assertNotified('Supplier\'s invoice recorded');
});

it('numbers posted documents in one series per prefix, with no gaps', function (): void {
    Illuminate\Support\Facades\Schema::create('document_numbers', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->string('prefix')->unique();
        $table->unsignedInteger('last');
    });
    Illuminate\Support\Facades\DB::table('document_numbers')->insert([['prefix' => 'SI', 'last' => 0], ['prefix' => 'PO', 'last' => 41]]);
    $next = fn (string $prefix): string => Josephdlmd\FilamentStorybook\Stories\Documents\DocumentNumber::next($prefix);

    expect([$next('SI'), $next('SI'), $next('PO')])->toBe(['SI-000001', 'SI-000002', 'PO-000042'])
        ->and(fn () => $next('RR'))->toThrow(RuntimeException::class, 'no number series for RR');
});

it('checks a payment against the balance due, with the tax withheld', function (array $data, ?string $error): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\PaymentStories::class, 'story' => 'default'])
        ->callAction(Filament\Actions\Testing\TestAction::make('recordPayment')->schemaComponent('story', 'form'), ['paid_on' => today()->toDateString(), 'number' => '004518', ...$data]);

    $error === null ? $canvas->assertHasNoActionErrors() : $canvas->assertHasActionErrors([array_key_first($data) === 'number' ? 'number' : 'amount' => $error]);
})->with([
    'a receipt serial already recorded' => [['number' => '004516', 'amount' => '1000'], 'Enter the serial of the receipt you wrote; receipt 004516 is already recorded'],
    'part of the balance' => [['amount' => '50000', 'tax_withheld' => '500', 'vat_withheld' => '2500'], null],
    'the whole balance, net of both taxes' => [['amount' => '106000.00', 'tax_withheld' => '1000.00', 'vat_withheld' => '5000.00'], null],
    'only the taxes, the cash paid earlier' => [['amount' => '0', 'tax_withheld' => '1000', 'vat_withheld' => '5000'], null],
    'more than the balance' => [['amount' => '106000.00', 'tax_withheld' => '1000.00', 'vat_withheld' => '5000.01'], 'Amount received and taxes withheld together must be ₱112,000.00 or less, the balance due'],
    'nothing' => [['amount' => '0', 'tax_withheld' => '', 'vat_withheld' => ''], 'Enter the amount received, the tax withheld, or both'],
]);

it('picks a customer by code or name, showing both', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordPickerStories::class, 'story' => 'default'])
        ->assertSee('C-0003 Cebu Diagnostics Laboratory');

    $select = $canvas->instance()->form->getComponent('customer_id');

    expect($select->getSearchResults('C-0004'))->toBe([4 => 'C-0004 Davao Family Clinic'])
        ->and($select->getSearchResults('iloilo'))->toBe([5 => 'C-0005 Iloilo Dental Group']);
});

it('proposes what a government office withholds: 1% income tax and 5% VAT on the amount less VAT, and the cash expected', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\PaymentStories::class, 'story' => 'default'])
        ->mountAction(Filament\Actions\Testing\TestAction::make('recordPayment')->schemaComponent('story', 'form'))
        ->assertSchemaStateSet(['amount' => '106000.00', 'tax_withheld' => '1000.00', 'vat_withheld' => '5000.00'], 'mountedActionSchema0');

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals::expectedWithholding('2363.71', '3363.71', '360.40', '0.01'))->toBe('21.10');
});

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

    $error === null ? $canvas->assertHasNoActionErrors() : $canvas->assertHasActionErrors(['number' => $error]);
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
