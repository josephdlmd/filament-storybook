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
        ->toBe(['actions-delete', 'forms-choice', 'forms-date', 'forms-file-upload', 'forms-line-items', 'forms-money-input', 'forms-quantity', 'forms-record-form', 'forms-tin', 'forms-unit-cost', 'forms-yes-or-no', 'records-details', 'records-locked', 'records-view-page', 'tables-list-table', 'tables-money-column', 'tables-status-badge']);
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

    expect($api->keys()->all())->toContain('Table::columns', 'TextColumn::make', 'TextColumn::money', 'SelectFilter::options', 'Table::defaultSort')
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

it('tells an agent the valid ids instead of letting it guess', function (): void {
    expect((string) (new ShowStories)->handle(new Request(['id' => 'nope']))->content())
        ->toContain('Valid ids: actions-delete, forms-choice, forms-date, forms-file-upload, forms-line-items, forms-money-input, forms-quantity, forms-record-form, forms-tin, forms-unit-cost, forms-yes-or-no, records-details, records-locked, records-view-page, tables-list-table, tables-money-column, tables-status-badge');
});

it('reports every story passing', function (): void {
    expect((string) (new TestStories)->handle(new Request)->content())
        ->toStartWith('# 23 passing, 0 failing, 0 warnings, 0 accessibility issues')
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
        ->toStartWith('# 2 passing, 3 failing, 5 warnings, 1 accessibility issues')
        ->toContain('- `broken-story.unnamedIconButton`: [critical] button-name: Buttons must have discernible text')
        ->toContain('- `broken-story`: add description()')
        ->toContain('- `broken-story.works`: no placeholder()')
        ->toContain('- `broken-story.throws`: add a one-line docblock')
        ->toContain('- `broken-story.failsWhileRendering`: Error: Call to a member function upper() on string at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:41')
        ->toContain('- `broken-story.throws`: RuntimeException: Story exploded at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:25')
        ->toContain('- `broken-story.untyped`: Not a story: return a Filament component, table column or action, or take and return a Table or Schema, at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:28')
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
        ->toStartWith('# 2 pages rendered, 0 failing, 14 warnings')
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
        // A line-item field built right, under its own label as the header, raises nothing.
        ->not->toContain('`part`')
        ->not->toContain('"Part"')
        ->not->toContain('the error "Enter name"')
        ->not->toContain('25 rows')
        ->toContain('- `EditGadget`: no record to open')
        ->toContain('- `ViewGadget`: no record to open')
        ->toContain('Fix each one, then run storybook-audit again');
});

it('audits edit pages against an existing record', function (): void {
    Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5]);

    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->toStartWith('# 4 pages rendered, 0 failing, 25 warnings')
        ->toContain('- `EditGadget`: `delete` is a solid danger button beside other actions')
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

it('links a missing value to where it is added', function (): void {
    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordDetailsStories::class, 'story' => 'default'])
        ->assertSee('Add due date')
        ->assertSee('123-456-789-000')
        ->assertSee('₱2,363.71');
});

it('keeps a locked record\'s values out of the save, even when changed in the browser', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordLockedStories::class, 'story' => 'default'])
        ->assertSee('This bill is approved, so only its notes can change')
        ->assertSeeHtml('readonly')
        ->assertSee('30 days')
        ->set('data.reference', 'CHANGED')
        ->set('data.amount', '1')
        ->set('data.notes', 'Checked by the warehouse');

    expect($canvas->instance()->form->getState())->toBe(['notes' => 'Checked by the warehouse']);
});
