<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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
        return 'The lines and totals of a sales invoice, purchase order or bill: each line has a VAT class, and under the lines come the VATable sales, the VAT, the zero-rated and VAT-exempt sales, and the total amount due. Unit prices include VAT, so the VAT is 12/112 of the VATable total, worked out once per document and rounded once to centavos, half up; the VATable sales are that total less the VAT. All of it is decimal arithmetic in the `DocumentTotals` class (copy it from the examples); never PHP floats. Withholding tax is not on the document: it is taken when the document is paid (`actions-record-payment`), so the total amount due is before withholding. The totals are read-only text, right-aligned. Work them out again with `DocumentTotals::of($lines)` when saving and store them in decimal columns; never save what the form showed. The lines follow `forms-line-items`. Business standards from the owner (2026-10-10 and 2026-10-11): VAT per document for each class, prices including VAT, half-up rounding; BIR RR 7-2024 (VAT shown as a separate item, VATable, zero-rated and exempt sales shown apart). Basis: Carbon text input (read-only, not disabled, for a value to read), Carbon forms (labels above), USWDS table (numbers right-aligned).';
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
        ]]];
    }

    /** An invoice's lines with the totals under them, worked out as the user types. */
    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Repeater::make('lines')
                    ->table([
                        TableColumn::make('Description')->markAsRequired()->alignStart(),
                        TableColumn::make('VAT class')->markAsRequired()->alignStart()->width('9rem'),
                        TableColumn::make('Quantity')->markAsRequired()->alignEnd()->width('7rem'),
                        TableColumn::make('Unit price')->markAsRequired()->alignEnd()->width('9rem'),
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
                ]),
            ]);
    }
}
