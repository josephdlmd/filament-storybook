<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Josephdlmd\FilamentStorybook\Stories;

class LineItemsStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Line items';
    }

    public function description(): string
    {
        return 'The lines of an invoice, bill or purchase order: a table repeater with one column per field. Each column header is its field\'s label word for word, and no field uses `hiddenLabel()`. The plugin\'s house CSS keeps each field\'s label for screen readers under the header, and Filament shows it on screen when rows stack on narrow screens. Quantity has up to 3 decimals and unit price up to 4 (`forms-quantity`, `forms-unit-cost`). The line total is read-only text: quantity × unit price, rounded once to 2 decimals, half up (away from zero for a negative line). Work it out again on the server when saving; never save the shown value. Reordering is off: Filament reorders by dragging, which needs a way without dragging, so if the order matters add `reorderableWithButtons()`. When a line has more fields than fit without truncation, use `longRow`. Business standards from the owner (2026-10-10). Basis: WCAG 1.3.1, 2.5.3, 2.5.7, 3.3.2 and 4.1.2; W3C WAI forms tutorial 2024 (a label may be hidden on screen when the context gives it, never removed); Carbon text input (read-only, not disabled, for a value to read) and data table (not for complex interactions); USWDS table (numbers right-aligned).';
    }

    public function records(): array
    {
        return [1 => ['lines' => [
            ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.5', 'account' => '5100', 'notes' => null],
            ['description' => 'Isopropyl alcohol 70%, 500 mL', 'quantity' => '2.5', 'unit_price' => '89.2857', 'account' => '5100', 'notes' => 'Partial case'],
        ]]];
    }

    public function default(): Repeater
    {
        return Repeater::make('lines')
            ->table([
                TableColumn::make('Description')->markAsRequired()->alignStart(),
                TableColumn::make('Quantity')->markAsRequired()->alignEnd()->width('7rem'),
                TableColumn::make('Unit price')->markAsRequired()->alignEnd()->width('9rem'),
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
                    ->state(function (Get $get): ?string {
                        if (! is_numeric($get('quantity')) || ! is_numeric($get('unit_price'))) {
                            return null;
                        }

                        // Filament keeps numbers as floats; formatting each to its own decimals gives back exactly what was typed.
                        $exact = bcmul(number_format($get('quantity'), 3, '.', ''), number_format($get('unit_price'), 4, '.', ''), 7);

                        // Half up, away from zero for a negative line, so a credit mirrors the sale.
                        return str_starts_with($exact, '-') ? bcsub($exact, '0.005', 2) : bcadd($exact, '0.005', 2);
                    })
                    ->money('PHP')
                    ->alignEnd(),
            ])
            ->addActionLabel('Add line')
            ->reorderable(false)
            ->minItems(1)
            ->defaultItems(1);
    }

    /** A line with more fields than fit without truncation: each line is a small form with every label on screen. */
    public function longRow(): Repeater
    {
        return Repeater::make('lines')
            ->schema([
                TextInput::make('description')
                    ->required()
                    ->maxLength(255)
                    ->maxWidth('2xl'),
                Select::make('account')
                    ->options([
                        '5100' => '5100 Medical supplies',
                        '5200' => '5200 Office supplies',
                        '5300' => '5300 Repairs and maintenance',
                    ])
                    ->searchable()
                    ->required()
                    ->maxWidth('md'),
                TextInput::make('quantity')
                    ->numeric()
                    ->step(0.001)
                    ->minValue(0)
                    ->rules(['decimal:0,3'])
                    ->required()
                    ->live(onBlur: true)
                    ->maxWidth('xs')
                    ->validationMessages(['decimal' => 'Enter the quantity with at most 3 decimal places, such as 12.5']),
                TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.0001)
                    ->minValue(0)
                    ->rules(['decimal:0,4'])
                    ->required()
                    ->live(onBlur: true)
                    ->maxWidth('xs')
                    ->validationMessages(['decimal' => 'Enter the unit price with at most 4 decimal places, such as 89.2857']),
                TextEntry::make('line_total')
                    ->state(function (Get $get): ?string {
                        if (! is_numeric($get('quantity')) || ! is_numeric($get('unit_price'))) {
                            return null;
                        }

                        // Filament keeps numbers as floats; formatting each to its own decimals gives back exactly what was typed.
                        $exact = bcmul(number_format($get('quantity'), 3, '.', ''), number_format($get('unit_price'), 4, '.', ''), 7);

                        // Half up, away from zero for a negative line, so a credit mirrors the sale.
                        return str_starts_with($exact, '-') ? bcsub($exact, '0.005', 2) : bcadd($exact, '0.005', 2);
                    })
                    ->money('PHP'),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(2)
                    ->maxWidth('2xl'),
            ])
            ->addActionLabel('Add line')
            ->reorderable(false)
            ->minItems(1)
            ->defaultItems(1);
    }
}
