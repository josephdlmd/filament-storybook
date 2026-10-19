<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp\AuditPages;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;

it('imports a CSV row with the form\'s checks, errors and stored formats, and shows agents the importer to copy', function (): void {
    Illuminate\Support\Facades\Schema::create('suppliers', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');
        $table->decimal('credit_limit', 15, 2)->nullable();
        $table->timestamps();
    });

    $import = new Josephdlmd\FilamentStorybook\Stories\Imports\SupplierImporter(new Filament\Actions\Imports\Models\Import, ['code' => 'code', 'name' => 'name', 'credit_limit' => 'credit_limit'], []);

    $import(['code' => 'SUP-0001', 'name' => 'Abra Medical Supply', 'credit_limit' => '250000.50']);
    // The same code, typed another way, updates that supplier.
    $import(['code' => ' sup-0001 ', 'name' => 'Abra Medical Supply Inc.', 'credit_limit' => '250000.50']);

    // The amount reaches the model as typed, never a float (SQLite itself reads decimals back as floats).
    expect(Josephdlmd\FilamentStorybook\Stories\Imports\Supplier::query()->get(['code', 'name'])->toArray())
        ->toBe([['code' => 'SUP-0001', 'name' => 'Abra Medical Supply Inc.']])
        ->and($import->getData()['credit_limit'])->toBe('250000.50')
        ->and(fn () => $import(['code' => '', 'name' => 'Benguet Trading', 'credit_limit' => '1']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the code')
        ->and(fn () => $import(['code' => 'SUP-0002', 'name' => 'Benguet Trading', 'credit_limit' => '1.234']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Enter the credit limit with at most 2 decimal places');

    expect(storybook('show', ['ids' => ['tables-import-and-export']]))
        ->toContain('## SupplierImporter')
        ->toContain('public function resolveRecord(): Supplier');
});

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

it('numbers posted documents in one series per prefix, with no gaps', function (): void {
    Illuminate\Support\Facades\Schema::create('document_numbers', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->string('prefix')->unique();
        $table->unsignedInteger('last');
    });
    Illuminate\Support\Facades\DB::table('document_numbers')->insert([['prefix' => 'SI', 'last' => 0], ['prefix' => 'PO', 'last' => 41]]);
    $next = fn (string $prefix): string => Illuminate\Support\Facades\DB::transaction(fn (): string => Josephdlmd\FilamentStorybook\Documents\DocumentNumber::next($prefix));

    expect([$next('SI'), $next('SI'), $next('PO')])->toBe(['SI-000001', 'SI-000002', 'PO-000042'])
        ->and(fn () => $next('RR'))->toThrow(RuntimeException::class, 'no number series for RR')
        ->and(fn () => Josephdlmd\FilamentStorybook\Documents\DocumentNumber::next('SI'))->toThrow(RuntimeException::class, 'inside the transaction');

    // A posting that fails after taking its number gives the number back.
    rescue(fn () => Illuminate\Support\Facades\DB::transaction(function (): void {
        Josephdlmd\FilamentStorybook\Documents\DocumentNumber::next('SI');

        throw new RuntimeException('Posting failed');
    }), report: false);

    expect($next('SI'))->toBe('SI-000003');
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
    Spatie\LaravelPdf\Facades\Pdf::assertSee(['Sales invoice (copy)', '001232', 'Cebu Diagnostics Laboratory', '₱3,426.00', '₱6,326.00', '<html lang="en">', '<title>Sales invoice (copy) 001232</title>', '<th scope="col"']);
    $pdf = (fn (): Spatie\LaravelPdf\PdfBuilder => $this->generatedPdfs[0])->call(Spatie\LaravelPdf\Facades\Pdf::getFacadeRoot());

    expect($pdf->tagged)->toBeTrue()
        ->and((fn (): ?string => $this->format)->call($pdf))->toBe('a4');
});

it('lists a customer\'s open invoices on a statement, with each ageing bucket and the total due', function (): void {
    $customer = (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard', 'tin' => '987-654-321-000']);
    $invoice = fn (string $number, string $due, string $balance) => (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['number' => $number, 'invoice_date' => '2026-08-01', 'due_date' => $due, 'balance_due' => $balance]);
    $statement = Josephdlmd\FilamentStorybook\Documents\PrintedDocument::statementOfAccount($customer, [
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

it('acknowledges a collection under its receipt\'s serial', function (): void {
    $payment = (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['number' => '000387', 'paid_on' => '2026-11-08', 'amount' => '6326.00', 'reference' => 'Check 0047731']);

    $document = Josephdlmd\FilamentStorybook\Documents\PrintedDocument::paymentAcknowledgement($payment);

    expect($document['number'])->toBe('000387')
        ->and($document['totals'])->toBe(['Amount received' => '6326.00'])
        ->and($document)->not->toHaveKey('statement');
});

it('emails a document as its PDF from the company address, and tells the sender when it has gone or failed', function (): void {
    Spatie\LaravelPdf\Facades\Pdf::fake();
    config(['mail.default' => 'array', 'queue.default' => 'sync']);
    $sender = User::forceCreate(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secret']);
    $document = ['title' => 'Statement of account', 'number' => 'Nov 30, 2026'] + Josephdlmd\FilamentStorybook\Documents\PrintedDocument::statementOfAccount((new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['name' => 'Cebu Diagnostics Laboratory']), []);
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
    'collections' => [Josephdlmd\FilamentStorybook\Stories\CollectionsReportStories::class, 'default', 'Collections report', ['004516', '₱6,326.00', 'All dates']],
    'ageing' => [Josephdlmd\FilamentStorybook\Stories\ReceivablesAgeingStories::class, 'default', 'Receivables ageing', ['As of', 'Abra Medical Supply', 'Total due']],
    'purchases' => [Josephdlmd\FilamentStorybook\Stories\PurchasesReportStories::class, 'default', 'Purchases report', ['PO-000043', '₱14,000.00']],
    'payables' => [Josephdlmd\FilamentStorybook\Stories\PurchasesReportStories::class, 'payables', 'Payables', ['PO-000042', 'Days past due', '₱12,298.00']],
]);

it('counts days past due from the day after the due date, in the report and on the statement alike', function (): void {
    $days = Josephdlmd\FilamentStorybook\Documents\PrintedDocument::daysPastDue(...);
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
        ['id' => 1, 'description' => 'Gloves', 'quantity' => '100', 'delivered' => '40', 'unit_price' => '285.50'],
        ['id' => 2, 'description' => 'Pens', 'quantity' => '20', 'delivered' => '20', 'unit_price' => '1450.00'],
    ];

    expect($F::remaining('12.5', '0.25'))->toBe('12.250')
        ->and($F::status($lines))->toBe('Partly delivered')
        ->and($F::status([['quantity' => '5', 'delivered' => '0']]))->toBe('Not delivered')
        ->and($F::status([['quantity' => '5', 'delivered' => '5']]))->toBe('Delivered')
        ->and($F::nextLines($lines, [1 => '60', 2 => '0']))->toBe([
            ['source_line_id' => 1, 'description' => 'Gloves', 'unit_price' => '285.50', 'quantity' => '60'],
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
    $statement = Josephdlmd\FilamentStorybook\Documents\PrintedDocument::supplierStatement('Luzon Medical Gases', [
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

it('leaves a missing date off the printed copy instead of printing today', function (): void {
    $invoice = (new Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice)->forceFill(['number' => '001232', 'invoice_date' => '2026-10-11', 'terms' => 'Cash', 'due_date' => null]);

    expect(Josephdlmd\FilamentStorybook\Documents\PrintedDocument::salesInvoice($invoice)['details'])
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

it('adds a note to a document, saying who wrote it', function (): void {
    $this->actingAs($user = User::forceCreate(['name' => 'Carla Diaz', 'email' => 'carla@example.com', 'password' => 'secret']));

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\RecordNotesStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertSeeInOrder(['Customer asked', 'Ana Reyes', 'Check promised', 'Ben Santos'])
        ->callAction(TestAction::make('create')->table(), ['body' => '  Spoke to their accountant.  '])
        ->assertHasNoActionErrors();

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice::find(2)->notes()->latest('id')->first())
        ->body->toBe('Spoke to their accountant.')
        ->user_id->toBe($user->id);
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

    // A month with no orders still shows, as 0, rather than being left out.
    $chart = Livewire::test(Josephdlmd\FilamentStorybook\Stories\Widgets\OrdersPerMonthChart::class, ['pageFilters' => ['from' => '2026-04-01', 'until' => '2026-06-30']]);

    expect($chart->instance()->getChartAssistiveContent())->toBe('Approved orders per month: April 0, May 42, June 38.');
});

it('names a report\'s export after the report and the day, as its PDF is', function (string $exporter, string $name): void {
    $export = new Filament\Actions\Exports\Models\Export;
    $export->created_at = Illuminate\Support\Carbon::parse('2026-10-13 09:00');

    expect((new $exporter($export, [], []))->getFileName($export))->toBe($name);
})->with([
    'sales' => [Josephdlmd\FilamentStorybook\Stories\Reports\SalesReportExporter::class, 'Sales report Oct 13, 2026'],
    'collections' => [Josephdlmd\FilamentStorybook\Stories\Reports\CollectionsReportExporter::class, 'Collections report Oct 13, 2026'],
]);

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

it('posts a draft only once, saying so when another user has already posted it', function (): void {
    $post = TestAction::make('post')->schemaComponent('story', 'form');
    $canvas = fn () => Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\DocumentLifecycleStories::class, 'story' => 'default']);

    $canvas()->callAction($post, ['number' => '001233'])->assertNotified('Invoice 001233 posted');
    // A second page, still showing the draft, posts it again under another serial.
    $canvas()->callAction($post, ['number' => '001235'])->assertNotified('Invoice 001235 not posted');

    expect(Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice::find(4)->number)->toBe('001233')
        ->and(Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice::where('number', '001235')->exists())->toBeFalse();
});

it('ages an invoice with no due date as current, in the report and on the statement alike', function (): void {
    Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice::query()->insert(['number' => '001250', 'status' => 'posted', 'customer_id' => 1, 'invoice_date' => '2026-10-01', 'due_date' => null, 'total' => '100.00', 'balance_due' => '100.00']);

    $abra = Josephdlmd\FilamentStorybook\Stories\Reports\ReceivablesAgeing::addBuckets(Josephdlmd\FilamentStorybook\Stories\Documents\Customer::query(), Illuminate\Support\Carbon::parse('2026-10-14'))
        ->where('code', 'C-0001')
        ->sole();

    expect((float) $abra->current)->toBe(100.0)
        ->and((float) $abra->current + $abra->days_1_30 + $abra->days_31_60 + $abra->days_61_90 + $abra->over_90)->toBe((float) $abra->balance_due);

    $customer = (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['name' => 'Abra Provincial Hospital']);
    $invoice = (new class extends Illuminate\Database\Eloquent\Model {})->forceFill(['number' => '001250', 'invoice_date' => '2026-10-01', 'due_date' => null, 'balance_due' => '100.00']);
    $statement = Josephdlmd\FilamentStorybook\Documents\PrintedDocument::statementOfAccount($customer, [$invoice], Illuminate\Support\Carbon::parse('2026-10-14'));

    expect($statement['totals']['Current'])->toBe('100.00')
        ->and($statement['lines'][0]['due'])->toBeNull()
        ->and($statement['lines'][0]['days'])->toBeNull();
});

it('names a list\'s export after the list and the day, and counts its rows by their name', function (): void {
    $export = new Filament\Actions\Exports\Models\Export;
    $export->created_at = Illuminate\Support\Carbon::parse('2026-10-13 09:00');
    $export->successful_rows = 2;
    $export->total_rows = 2;

    expect((new Josephdlmd\FilamentStorybook\Stories\Imports\SupplierExporter($export, [], []))->getFileName($export))->toBe('Suppliers Oct 13, 2026')
        ->and(Josephdlmd\FilamentStorybook\Stories\Imports\SupplierExporter::getCompletedNotificationBody($export))->toBe('2 suppliers exported.');
});
