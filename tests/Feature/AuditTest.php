<?php

use Josephdlmd\FilamentStorybook\Mcp\AuditPages;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;

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

it('warns when global search loads a related value one result at a time, and leaves lazy loading as the app had it', function (): void {
    $owner = User::forceCreate(['name' => 'Ana', 'email' => 'ana.owner@example.com', 'password' => 'secret']);
    Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget::create(['code' => 'GAD-0001', 'name' => 'Torch', 'price' => 99.5, 'owner_id' => $owner->id]);
    Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget::create(['code' => 'GAD-0002', 'name' => 'Lamp', 'price' => 45, 'owner_id' => $owner->id]);
    $audit = fn (): string => (string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content();
    $warning = '- `GadgetResource`: global search loads `owner` one result at a time';

    Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource::$ownerInSearch = true;

    try {
        expect($audit())->toContain($warning)
            ->and(Illuminate\Database\Eloquent\Model::preventsLazyLoading())->toBeFalse();

        Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource::$eagerSearch = true;

        expect($audit())->not->toContain($warning);
    } finally {
        Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource::$ownerInSearch = false;
        Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource::$eagerSearch = false;
    }
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
        ->toContain('- `GadgetResource PartsRelationManager`: the column "Created At" is in Title Case')
        ->toContain('- `GadgetResource PartsRelationManager`: the empty list offers 2 actions')
        ->toContain('- `GadgetResource PartsRelationManager`: the table shows 2 column(s); a table needs three or more')
        ->toContain('- `GadgetResource PartsRelationManager`: the empty list\'s button is a second primary')
        ->toContain('- `GadgetResource PartsRelationManager`: `delete` is labelled "Remove", but it destroys the record')
        ->toContain('- `GadgetResource PartsRelationManager`: `delete` asks "Are you sure you would like to do this?"')
        ->not->toContain('SparePartsRelationManager`:')
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

it('checks row actions whose label or tooltip needs the record', function (): void {
    expect((string) (new AuditPages)->handle(new Request(['resources' => ['GadgetResource']]))->content())
        ->toContain('0 failing')
        ->toContain('- `ListGadgets`')
        ->not->toContain('TypeError');
});

it('leaves no query parameters behind for the pages and tests after a page that sends the user back to itself', function (): void {
    (new AuditPages)->handle(new Request);

    // Opened without a period, the overview sends the user back to itself with one.
    expect(Livewire::test(Josephdlmd\FilamentStorybook\Tests\Fixtures\GadgetOverview::class)->instance())->toBeNull();
});

it('takes resources by short or full class name, as a list or one name, and refuses names it doesn\'t know', function (): void {
    expect((string) (new AuditPages)->handle(new Request(['resources' => GadgetResource::class]))->content())->toContain('- `CreateGadget`')
        ->and((string) (new AuditPages)->handle(new Request(['resources' => 'GadgetResource']))->content())->toContain('- `CreateGadget`')
        ->and($missing = (new AuditPages)->handle(new Request(['resources' => ['GadgetResorce', 'GadgetResource']])))
        ->and($missing->isError())->toBeTrue()
        ->and((string) $missing->content())->toContain('GadgetResorce')->toContain('GadgetResource')->not->toContain('GadgetResorce, GadgetResource');
});

it('reports a resource or widget that fails outside its pages, such as an unmigrated table, and audits the rest', function (): void {
    GadgetResource::$unmigrated = true;
    Josephdlmd\FilamentStorybook\Tests\Fixtures\GadgetStats::$unmigrated = true;

    try {
        expect((string) (new AuditPages)->handle(new Request)->content())
            ->toContain('- `GadgetResource`: RuntimeException: no such table: gadgets at ')
            ->toContain('- `GadgetStats`: RuntimeException: no such table: stock at ')
            ->toContain('- `GadgetOverview`')
            ->toContain('- `GadgetSalesChart`');
    } finally {
        GadgetResource::$unmigrated = false;
        Josephdlmd\FilamentStorybook\Tests\Fixtures\GadgetStats::$unmigrated = false;
    }
});

it('returns a timed-out accessibility check as not checked, keeping the rest of the result', function (): void {
    Illuminate\Support\Facades\Process::fake(fn (Illuminate\Process\PendingProcess $process) => $process->timeout === 90
        ? throw new Symfony\Component\Process\Exception\ProcessTimedOutException(new Symfony\Component\Process\Process(['node']), Symfony\Component\Process\Exception\ProcessTimedOutException::TYPE_GENERAL)
        : 1);

    [$issues, $unchecked] = Josephdlmd\FilamentStorybook\Accessibility::check(['page' => '<p>Gadgets</p>']);

    expect($issues)->toBe([])->and($unchecked)->toContain('Accessibility not checked')->toContain('90 seconds');
});
