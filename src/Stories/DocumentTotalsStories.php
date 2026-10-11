<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals;

class DocumentTotalsStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Document totals';
    }

    public function description(): string
    {
        return 'The lines and totals of a sales invoice, purchase order or bill: each line has a VAT class, and under the lines come the VATable sales, the VAT, the zero-rated and VAT-exempt sales, and the total amount due. Unit prices include VAT, so the VAT is 12/112 of the VATable total, worked out once per document and rounded once to centavos, half up; the VATable sales are that total less the VAT. All of it is decimal arithmetic in the `DocumentTotals` class (copy it from the examples); never PHP floats. A sales invoice\'s total amount due is before withholding; what a customer withholds is recorded with their payment (`actions-record-payment`). A supplier\'s invoice is recorded on its purchase order\'s View page, as printed (`supplierInvoice`): its total and its VAT as the supplier wrote them, since the printed VAT is what supports the input tax, with the order\'s own VAT beside it so a typing mistake stands out. The tax withheld from it is worked out once, there: the rate of the supplier\'s ATC (their default, changeable on the invoice) on the total less VAT, with `DocumentTotals::withholding()`. Payments then settle the net payable, and the order\'s BIR Form 2307 shows that figure; store the supplier\'s TIN and the ATC with it. The two here apply only to a company BIR has made a top withholding agent, which `config/company.php` says (`top_withholding_agent`); add the others the CPA confirms, such as rent or professional fees, which apply either way. The totals are read-only text, right-aligned. Work them out again with `DocumentTotals::of($lines)` when saving and store them in decimal columns; never save what the form showed. The lines follow `forms-line-items`: on a resource, add `->relationship()` to the repeater, so each line is a row of its own table. Business standards from the owner (2026-10-10 and 2026-10-11): VAT per document for each class, prices including VAT, half-up rounding; BIR RR 7-2024 (VAT shown as a separate item, VATable, zero-rated and exempt sales shown apart). Basis: Carbon text input (read-only, not disabled, for a value to read), Carbon structured list (the totals as terms and definitions, each label beside its amount, as on the printed document), USWDS table (numbers right-aligned).';
    }

    public function examples(): array
    {
        return [DocumentTotals::class];
    }

    public function records(): array
    {
        return [1 => ['lines' => [
            ['description' => 'Nitrile examination gloves, medium, box of 100', 'vat_class' => 'vatable', 'quantity' => '12', 'unit_price' => '285.5'],
            ['description' => 'Insulin pens, 3 mL, pack of 5', 'vat_class' => 'exempt', 'quantity' => '2', 'unit_price' => '1450'],
            ['description' => 'Isopropyl alcohol 70%, 500 mL', 'vat_class' => 'vatable', 'quantity' => '2.5', 'unit_price' => '89.2857'],
        ], 'number' => 'PO-000042', 'vat' => '567.64', 'supplier_atc' => 'WC158']];
    }

    /** An invoice's lines with the totals under them, worked out as the user types. */
    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Repeater::make('lines')
                    ->table([
                        TableColumn::make('Description')->alignStart(),
                        TableColumn::make('VAT class')->alignStart()->width('8rem'),
                        TableColumn::make('Quantity')->alignEnd()->width('6rem'),
                        TableColumn::make('Unit price')->alignEnd()->width('9rem'),
                        TableColumn::make('Line total')->alignEnd()->width('8rem'),
                    ])
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255),
                        Select::make('vat_class')
                            ->label('VAT class')
                            ->options(DocumentTotals::VAT_CLASSES)
                            ->default('vatable')
                            ->required()
                            ->live(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->rules(['decimal:0,3'])
                            ->required()
                            ->live(onBlur: true)
                            ->extraInputAttributes(['style' => 'text-align: end'])
                            ->validationMessages(['decimal' => 'Enter the quantity with at most 3 decimal places, such as 12.5']),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->prefix('₱')
                            ->step(0.0001)
                            ->minValue(0)
                            ->rules(['decimal:0,4'])
                            ->required()
                            ->live(onBlur: true)
                            ->extraInputAttributes(['style' => 'text-align: end'])
                            ->validationMessages(['decimal' => 'Enter the unit price with at most 4 decimal places, such as 89.2857']),
                        TextEntry::make('line_total')
                            ->state(fn (Get $get): ?string => DocumentTotals::lineTotal($get('quantity'), $get('unit_price')))
                            ->money('PHP')
                            ->alignEnd(),
                    ])
                    ->addActionLabel('Add line')
                    ->reorderable(false)
                    ->minItems(1)
                    ->defaultItems(1),
                // The totals as a list of labels and amounts, in the right half under the line totals; stacked when narrow.
                Grid::make(['@lg' => 2])
                    ->gridContainer()
                    ->schema([
                        Group::make([
                            TextEntry::make('vatable_sales')
                                ->label('VATable sales')
                                ->state(fn (Get $get): string => DocumentTotals::of($get('lines') ?? [])['vatable_sales'])
                                ->money('PHP')
                                ->alignEnd(),
                            TextEntry::make('vat')
                                ->label('VAT (12%)')
                                ->state(fn (Get $get): string => DocumentTotals::of($get('lines') ?? [])['vat'])
                                ->money('PHP')
                                ->alignEnd(),
                            TextEntry::make('zero_rated_sales')
                                ->label('Zero-rated sales')
                                ->state(fn (Get $get): string => DocumentTotals::of($get('lines') ?? [])['zero_rated_sales'])
                                ->money('PHP')
                                ->alignEnd(),
                            TextEntry::make('exempt_sales')
                                ->label('VAT-exempt sales')
                                ->state(fn (Get $get): string => DocumentTotals::of($get('lines') ?? [])['exempt_sales'])
                                ->money('PHP')
                                ->alignEnd(),
                            TextEntry::make('total')
                                ->label('Total amount due')
                                ->state(fn (Get $get): string => DocumentTotals::of($get('lines') ?? [])['total'])
                                ->money('PHP')
                                ->alignEnd(),
                        ])
                            ->inlineLabel()
                            ->columnStart(['@lg' => 2]),
                    ]),
            ]);
    }

    /** Recording a supplier's invoice on its purchase order: its figures as printed, and the tax withheld from its total, worked out once. */
    public function supplierInvoice(): Action
    {
        return Action::make('recordSupplierInvoice')
            ->label('Record supplier\'s invoice')
            ->color('gray')
            ->modalHeading(fn (?Model $record): string => "Record supplier's invoice for order {$record?->number}")
            ->schema([
                TextInput::make('supplier_invoice_number')
                    ->label('Supplier\'s invoice number')
                    ->required()
                    ->maxLength(30)
                    ->maxWidth('xs'),
                DatePicker::make('supplier_invoice_date')
                    ->label('Invoice date')
                    ->maxDate(today())
                    ->required()
                    ->maxWidth('xs'),
                TextInput::make('total')
                    ->label('Total as printed')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->rules(['decimal:0,2'])
                    ->required()
                    ->live(onBlur: true)
                    ->maxWidth('xs')
                    ->validationMessages(['decimal' => 'Enter the total with at most 2 decimal places, such as 5298.00']),
                TextInput::make('vat')
                    ->label('VAT as printed')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->rules(['decimal:0,2'])
                    ->required()
                    ->live(onBlur: true)
                    ->maxWidth('xs')
                    ->helperText(fn (?Model $record): string => 'The purchase order works out '.Number::currency((float) $record?->vat, 'PHP'))
                    ->validationMessages(['decimal' => 'Enter the VAT with at most 2 decimal places, such as 567.64']),
                Radio::make('withholding_code')
                    ->label('Withholding')
                    ->options(fn (): array => [
                        // A top withholding agent withholds from every regular supplier; BIR says which companies are.
                        ...(config('company.top_withholding_agent') ? ['WC158' => 'WC158', 'WC160' => 'WC160'] : []),
                        'none' => 'None',
                    ])
                    ->descriptions(['WC158' => '1% on goods', 'WC160' => '2% on services'])
                    ->default(fn (?Model $record): ?string => $record?->supplier_atc)
                    ->required()
                    ->live(),
                TextEntry::make('tax_withheld')
                    ->state(fn (Get $get): ?string => is_numeric($get('total')) && is_numeric($get('vat')) && filled($get('withholding_code'))
                        ? DocumentTotals::withholding(number_format((float) $get('total'), 2, '.', ''), number_format((float) $get('vat'), 2, '.', ''), ['WC158' => '0.01', 'WC160' => '0.02', 'none' => '0'][$get('withholding_code')])
                        : null)
                    ->money('PHP'),
                TextEntry::make('net_payable')
                    ->state(fn (Get $get): ?string => is_numeric($get('total')) && is_numeric($get('vat')) && filled($get('withholding_code'))
                        ? bcsub(number_format((float) $get('total'), 2, '.', ''), DocumentTotals::withholding(number_format((float) $get('total'), 2, '.', ''), number_format((float) $get('vat'), 2, '.', ''), ['WC158' => '0.01', 'WC160' => '0.02', 'none' => '0'][$get('withholding_code')]), 2)
                        : null)
                    ->money('PHP'),
            ])
            ->successNotificationTitle(fn (?Model $record): string => "Supplier's invoice for order {$record?->number} recorded")
            ->action(function (Model $record, array $data, Action $action): void {
                // On a resource: work the tax withheld out again with DocumentTotals::withholding(), store it with the supplier's TIN and the code,
                // and set the order's balance_due to the net payable.
                $action->success();
            });
    }
}
