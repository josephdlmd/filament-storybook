<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp\ListStories;
use Josephdlmd\FilamentStorybook\Mcp\ShowStories;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\ChoiceStories;
use Josephdlmd\FilamentStorybook\Stories\ListTableStories;
use Josephdlmd\FilamentStorybook\Stories\MoneyColumnStories;
use Josephdlmd\FilamentStorybook\Stories\MoneyInputStories;
use Josephdlmd\FilamentStorybook\Stories\RecordFormStories;
use Josephdlmd\FilamentStorybook\Stories\StatusBadgeStories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;

it('discovers the shipped stories, sorted by title', function (): void {
    expect(array_keys(app(StoryRegistry::class)->all()))
        ->toBe(['actions-adjust-stock', 'actions-approve-and-reject', 'actions-archive', 'actions-create-next-document', 'actions-credit-limit', 'actions-delete', 'actions-download-pdf', 'actions-download-supplier-statement', 'actions-email-document', 'actions-invite-a-user', 'actions-mark-2307-received', 'actions-record-payment', 'actions-void', 'dashboards-key-figures-and-charts', 'feedback-notifications', 'forms-choice', 'forms-contact-details', 'forms-date', 'forms-document-totals', 'forms-file-upload', 'forms-form-container', 'forms-line-items', 'forms-money-input', 'forms-percentage', 'forms-quantity', 'forms-record-form', 'forms-record-picker', 'forms-settings-page', 'forms-tin', 'forms-unit-cost', 'forms-yes-or-no', 'records-details', 'records-document-lifecycle', 'records-history', 'records-key-figures', 'records-locked', 'records-notes', 'records-paper-receipt', 'records-previous-and-next', 'records-related-records', 'records-stock', 'records-view-page', 'reports-collections', 'reports-purchases-and-payables', 'reports-receivables-ageing', 'reports-sales', 'reports-tax-withheld-by-customers', 'reports-tax-withheld-from-suppliers', 'tables-bulk-actions', 'tables-import-and-export', 'tables-list-table', 'tables-money-column', 'tables-status-badge']);
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

it('tells an agent the valid ids instead of letting it guess', function (): void {
    expect((string) (new ShowStories)->handle(new Request(['id' => 'nope']))->content())
        ->toContain('Valid ids: actions-adjust-stock, actions-approve-and-reject, actions-archive, actions-create-next-document, actions-credit-limit, actions-delete, actions-download-pdf, actions-download-supplier-statement, actions-email-document, actions-invite-a-user, actions-mark-2307-received, actions-record-payment, actions-void, dashboards-key-figures-and-charts, feedback-notifications, forms-choice, forms-contact-details, forms-date, forms-document-totals, forms-file-upload, forms-form-container, forms-line-items, forms-money-input, forms-percentage, forms-quantity, forms-record-form, forms-record-picker, forms-settings-page, forms-tin, forms-unit-cost, forms-yes-or-no, records-details, records-document-lifecycle, records-history, records-key-figures, records-locked, records-notes, records-paper-receipt, records-previous-and-next, records-related-records, records-stock, records-view-page, reports-collections, reports-purchases-and-payables, reports-receivables-ageing, reports-sales, reports-tax-withheld-by-customers, reports-tax-withheld-from-suppliers, tables-bulk-actions, tables-import-and-export, tables-list-table, tables-money-column, tables-status-badge');
});
