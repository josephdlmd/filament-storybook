<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Stories;

class RecordViewPageStories extends Stories
{
    public function title(): string
    {
        return 'Records/View page';
    }

    public function description(): string
    {
        return 'The View page of every record, pasted into the resource\'s `infolist()`: the content in three quarters, and the summary of facts in one quarter (`records-details`), stacked with the content first when the page is narrower than 56rem (a container query, so the open sidebar counts). The main column holds what the record is made of (its lines, as read-only tables), then its related lists, most used first, never in tabs beside the details. Filament shows several relation managers as tabs below the whole layout, so embed each in the main column, keeping its permission check: `Livewire::make(PaymentsRelationManager::class, fn (Bill $record): array => [\'ownerRecord\' => $record, \'pageClass\' => ViewBill::class])->key(\'payments\')->visible(fn (Bill $record): bool => PaymentsRelationManager::canViewForRecord($record, ViewBill::class))`, and have the page\'s `getRelationManagers()` return `[]`. The page\'s next step, such as Approve, is its one primary header action; Edit sits on the summary\'s heading row. The summary is not sticky. Values are read-only text, never Filament\'s default disabled form. Basis: Carbon 2x grid (16 columns divided by two and by four; Carbon advises against layouts in thirds) and read-only states (static information is shown as text, not as read-only or disabled fields); GOV.UK summary card; NN/g tabs 2024 (related content not in tabs); WCAG 1.3.2, 1.4.3, 1.4.10 and 2.4.11.';
    }

    public function records(): array
    {
        return [1 => [
            'number' => 'BILL-0042',
            'status' => 'for_approval',
            'supplier' => 'Abra Medical Supply',
            'supplier_tin' => '123-456-789-000',
            'bill_date' => '2026-10-02',
            'due_date' => null,
            'total' => '3363.71',
            'balance_due' => '2363.71',
            'lines' => [
                ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.5', 'line_total' => '3426.00'],
                ['description' => 'Isopropyl alcohol 70%, 500 mL', 'quantity' => '2.5', 'unit_price' => '89.2857', 'line_total' => '223.21'],
                ['description' => 'Returned: damaged box of gloves', 'quantity' => '-1', 'unit_price' => '285.5', 'line_total' => '-285.50'],
            ],
            'payments' => [
                ['paid_on' => '2026-10-05', 'reference' => 'CHK-1007', 'amount' => '1000'],
            ],
        ]];
    }

    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['@4xl' => 4])
                    ->gridContainer()
                    ->schema([
                        Group::make([
                            RepeatableEntry::make('lines')
                                ->table([
                                    TableColumn::make('Description'),
                                    TableColumn::make('Quantity')->alignEnd(),
                                    TableColumn::make('Unit price')->alignEnd(),
                                    TableColumn::make('Line total')->alignEnd(),
                                ])
                                ->schema([
                                    TextEntry::make('description'),
                                    TextEntry::make('quantity')
                                        ->numeric(maxDecimalPlaces: 3)
                                        ->alignEnd(),
                                    TextEntry::make('unit_price')
                                        ->money('PHP', decimalPlaces: 4)
                                        ->alignEnd(),
                                    TextEntry::make('line_total')
                                        ->money('PHP')
                                        ->alignEnd(),
                                ]),
                            RepeatableEntry::make('payments')
                                ->table([
                                    TableColumn::make('Paid on'),
                                    TableColumn::make('Reference'),
                                    TableColumn::make('Amount')->alignEnd(),
                                ])
                                ->schema([
                                    TextEntry::make('paid_on')
                                        ->date(),
                                    TextEntry::make('reference'),
                                    TextEntry::make('amount')
                                        ->money('PHP')
                                        ->alignEnd(),
                                ]),
                        ])
                            ->columnSpan(['@4xl' => 3]),
                        Section::make('Details')
                            ->afterHeader([
                                EditAction::make()->color('gray'),
                            ])
                            ->schema([
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => str($state)->headline())
                                    ->color(fn (string $state): string => match ($state) {
                                        'approved' => 'success',
                                        'for_approval' => 'warning',
                                        'overdue' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('supplier'),
                                TextEntry::make('supplier_tin')
                                    ->label('Supplier TIN'),
                                TextEntry::make('bill_date')
                                    ->date(),
                                TextEntry::make('due_date')
                                    ->date()
                                            ->belowContent(
                                        EditAction::make('addDueDate')
                                            ->label('Add due date')
                                            ->link()
                                            ->hidden(fn (TextEntry $component): bool => filled($component->getState())),
                                    ),
                                TextEntry::make('total')
                                    ->money('PHP'),
                                TextEntry::make('balance_due')
                                    ->money('PHP'),
                            ])
                            ->columnSpan(['@4xl' => 1]),
                    ]),
            ]);
    }
}
