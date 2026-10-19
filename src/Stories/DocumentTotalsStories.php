<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Attributes\Section;
use Josephdlmd\FilamentStorybook\Documents\DocumentTotals;
use Josephdlmd\FilamentStorybook\Stories;

class DocumentTotalsStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Document totals';
    }

    public function description(): string
    {
        return 'The lines and total of a sales invoice, purchase order or bill: under the lines comes the total amount due, worked out as the user types. Each line total is quantity × unit price rounded once to centavos, half up, and the total is their sum; all of it is decimal arithmetic in the package\'s `DocumentTotals` (call it), never PHP floats. The total is read-only text, right-aligned. Work it out again with `DocumentTotals::of($lines)` when saving and store it in a decimal column; never save what the form showed. The lines follow `forms-line-items`: on a resource, add `->relationship()` to the repeater, so each line is a row of its own table. An app that keeps Philippine books shows VAT and withholding with filament-storybook-ph (`forms-document-totals-with-vat`). Business standards from the owner (2026-10-10): half-up rounding, once. Basis: Carbon text input (read-only, not disabled, for a value to read), Carbon structured list (the total as a term and definition, its label beside its amount, as on the printed document), USWDS table (numbers right-aligned).';
    }

    public function examples(): array
    {
        return [DocumentTotals::class];
    }

    public function records(): array
    {
        return [1 => ['lines' => [
            ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.5'],
            ['description' => 'Insulin pens, 3 mL, pack of 5', 'quantity' => '2', 'unit_price' => '1450'],
            ['description' => 'Isopropyl alcohol 70%, 500 mL', 'quantity' => '2.5', 'unit_price' => '89.2857'],
        ]]];
    }

    /** An invoice's lines with the total under them, worked out as the user types. */
    #[Section]
    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Repeater::make('lines')
                    ->table([
                        TableColumn::make('Description')->alignStart(),
                        TableColumn::make('Quantity')->alignEnd()->width('6rem'),
                        TableColumn::make('Unit price')->alignEnd()->width('9rem'),
                        TableColumn::make('Line total')->alignEnd()->width('8rem'),
                    ])
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255),
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
                    // A document needs a line; required() also keeps the audit from asking for \"(optional)\".
                    ->required()
                    ->validationMessages(['required' => 'Add a line'])
                    ->defaultItems(1),
                // The totals as a list of labels and amounts, in the right half under the line totals; stacked when narrow.
                Grid::make(['@lg' => 2])
                    ->gridContainer()
                    ->schema([
                        Group::make([
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
}
