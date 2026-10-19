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
use Josephdlmd\FilamentStorybook\Attributes\Record;
use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Documents\PaymentStatus;
use Josephdlmd\FilamentStorybook\Stories;

class RecordViewPageStories extends Stories
{
    public function title(): string
    {
        return 'Records/View page';
    }

    public function description(): string
    {
        return 'The View page of every record, pasted into the resource\'s `infolist()`: the content in three quarters and the record\'s facts in one quarter (`records-details`), one of the splits Carbon\'s grid allows, stacked with the content first when the page is narrower than 56rem (a container query, so the open sidebar counts). The main column holds what the record is made of (its lines, as read-only tables), then its related lists, on the page rather than in tabs. Filament shows several relation managers as tabs below the whole layout, so embed each in the main column, keeping its permission check: `Livewire::make(PaymentsRelationManager::class, fn (PurchaseOrder $record): array => [\'ownerRecord\' => $record, \'pageClass\' => ViewPurchaseOrder::class])->key(\'payments\')->visible(fn (PurchaseOrder $record): bool => PaymentsRelationManager::canViewForRecord($record, ViewPurchaseOrder::class))`, and have the page\'s `getRelationManagers()` return `[]`. The page\'s next step, such as Approve while an order waits for approval or Record payment once it is posted, is its one primary header action. The summary is not sticky. Values are read-only text, never Filament\'s default disabled form. Basis: Carbon 2x grid (full width for complex product interfaces; 16 columns divided by two and by four, not thirds), structured list, tabs (no frequently used or critical content in tabs) and read-only states (static information is text, not read-only or disabled fields); NN/g tabs 2024; WCAG 1.3.2, 1.4.3 and 1.4.10.';
    }

    public function examples(): array
    {
        return [DocumentStatus::class, PaymentStatus::class];
    }

    public function records(): array
    {
        return [1 => [
            'number' => 'PO-000042',
            'status' => DocumentStatus::Posted,
            'payment_status' => PaymentStatus::PartlyPaid,
            'supplier' => 'Abra Medical Supply',
            'order_date' => '2026-10-02',
            'supplier_invoice_number' => '20391',
            'due_date' => null,
            'total' => '3649.21',
            'balance_due' => '2649.21',
            'lines' => [
                ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.5', 'line_total' => '3426.00'],
                ['description' => 'Isopropyl alcohol 70%, 500 mL', 'quantity' => '2.5', 'unit_price' => '89.2857', 'line_total' => '223.21'],
            ],
            'payments' => [
                ['paid_on' => '2026-10-05', 'reference' => '0048213', 'amount' => '1000'],
            ],
        ]];
    }

    #[Record]
    #[States('loading', 'error', 'forbidden')]
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
                                    TableColumn::make('Payment date'),
                                    TableColumn::make('Reference'),
                                    TableColumn::make('Amount paid')->alignEnd(),
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
                                    ->badge(),
                                TextEntry::make('payment_status')
                                    ->badge(),
                                TextEntry::make('supplier'),
                                TextEntry::make('order_date')
                                    ->date(),
                                TextEntry::make('supplier_invoice_number')
                                    ->label('Supplier\'s invoice number'),
                                TextEntry::make('due_date')
                                    ->date(),
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
