<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp\AuditPages;
use Josephdlmd\FilamentStorybook\Mcp\ShowStories;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Laravel\Mcp\Request;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;

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

it('claims customer withholding in the quarter it was paid, 2307 or not, a quarter at a time', function (): void {
    $CustomerWithholding = Josephdlmd\FilamentStorybook\Stories\Reports\CustomerWithholding::class;
    $sawt = fn (int $year, int $quarter): array => $CustomerWithholding::sawt($year, $quarter)->orderBy('customer')->get()
        ->map(fn ($row): array => [$row->tin, $row->customer, $row->tax_atc, number_format((float) $row->income_payment, 2, '.', ''), number_format((float) $row->tax_withheld, 2, '.', ''), number_format((float) $row->vat_withheld, 2, '.', '')])->all();

    // Still waiting, oldest first: Abra's October payment and Cebu's November one.
    expect($CustomerWithholding::awaiting()->pluck('number')->all())->toBe(['004532', '004516'])
        // October to December: Abra's and both of Cebu's, 2307 or not; the void payment is left out.
        ->and($sawt(2026, 4))->toBe([['234-567-890-000', 'Abra Medical Supply', 'WC158', '795.09', '7.95', '0.00'], ['987-654-321-000', 'Cebu Diagnostics Laboratory', 'WC158', '5734.31', '57.34', '150.17']])
        ->and($sawt(2026, 3))->toBe([['234-567-890-000', 'Abra Medical Supply', 'WC158', '4464.00', '44.64', '0.00']])
        ->and($sawt(2026, 1))->toBe([]);

    $this->travelTo('2026-10-12');

    Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\CustomerWithholdingStories::class, 'story' => 'default'])
        ->call('loadTable')
        ->assertSee(['Cebu Diagnostics Laboratory', '₱150.17', 'Abra Medical Supply', '₱7.95'])
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
    'QAP' => [Josephdlmd\FilamentStorybook\Stories\Reports\QapExporter::class, 'QAP Oct 13, 2026'],
    'SAWT' => [Josephdlmd\FilamentStorybook\Stories\Reports\SawtExporter::class, 'SAWT Oct 13, 2026'],
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
