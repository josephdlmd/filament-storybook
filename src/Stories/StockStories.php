<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Attributes\Section;
use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Item;
use Josephdlmd\FilamentStorybook\Stories\Documents\Stock;
use Josephdlmd\FilamentStorybook\Stories\Documents\StockMovement;

class StockStories extends Stories
{
    public function title(): string
    {
        return 'Records/Stock';
    }

    public function description(): string
    {
        return 'Stock in the one warehouse, kept by `Stock`: a posted receiving report adds to it at its unit cost and moves the weighted average cost, a posted delivery takes out at the average, and an approved adjustment corrects it (`actions-adjust-stock`). Nothing takes an item below zero: posting is refused, saying how many are on hand. A receiving report is made from a posted purchase order as a delivery is from a sales order, in parts (`actions-create-next-document`), its unit costs the order\'s. The items list (`default`) shows each item\'s stock on hand, reorder level, average cost and value, with "Low stock" (warning) at or below the reorder level; `lowStock` is the list to put on the dashboard, as the `table()` of a table widget (`php artisan make:filament-widget --table`); an item\'s View page lists its movements, oldest first, each with what was on hand after (`movements`). Quantities have 3 decimals and costs 4. Basis: the owner\'s business standards (2026-10-12: one warehouse, no lots or expiry dates, weighted average cost, no negative stock, adjustments with a reason and approval, a reorder level per item); USWDS table (right-align numbers); Carbon status indicators (warning for what needs attention).';
    }

    public function examples(): array
    {
        return [Stock::class, Item::class, StockMovement::class];
    }

    #[States('loading', 'empty', 'error', 'forbidden')]
    public function default(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No items yet')
            ->emptyStateDescription('Items you stock appear here with what is on hand.')
            ->query(Item::query()->selectRaw('items.*, on_hand * average_cost as stock_value'))
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('on_hand')
                    ->label('On hand')
                    ->numeric(maxDecimalPlaces: 3)
                    ->suffix(fn (?Model $record): string => " {$record?->unit}")
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('reorder_level')
                    ->label('Reorder level')
                    ->numeric(maxDecimalPlaces: 3)
                    ->alignEnd(),
                TextColumn::make('average_cost')
                    ->label('Average cost')
                    ->money('PHP', decimalPlaces: 4)
                    ->alignEnd(),
                TextColumn::make('stock_value')
                    ->label('Value')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(fn (?Model $record): ?string => $record && Stock::isLow($record) ? 'Low stock' : null)
                    ->badge()
                    ->color('warning'),
            ])
            ->searchPlaceholder('Search by code or name')
            ->filters([
                Filter::make('low_stock')
                    ->label('Low stock only')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('on_hand', '<=', 'reorder_level')),
            ]);
    }

    /** Items at or below their reorder level, most short first: the list for the dashboard. */
    #[Section]
    #[States('loading', 'empty')]
    public function lowStock(Table $table): Table
    {
        return $table
            ->query(Item::query()->whereColumn('on_hand', '<=', 'reorder_level')->orderByRaw('on_hand - reorder_level'))
            ->heading('Low stock')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('on_hand')
                    ->label('On hand')
                    ->numeric(maxDecimalPlaces: 3)
                    ->suffix(fn (?Model $record): string => " {$record?->unit}")
                    ->alignEnd(),
                TextColumn::make('reorder_level')
                    ->label('Reorder level')
                    ->numeric(maxDecimalPlaces: 3)
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No items are low')
            ->emptyStateDescription('Items at or below their reorder level appear here.');
    }

    /** An item's movements on its View page: each document, what came in or went out, and what was on hand after. */
    #[Section]
    #[States('loading', 'empty')]
    public function movements(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No stock movements yet')
            ->emptyStateDescription('Deliveries, receipts and adjustments of this item appear here.')
            ->query(StockMovement::query()->where('item_id', 1))
            ->heading('Stock movements')
            ->defaultSort('id')
            ->columns([
                TextColumn::make('moved_on')
                    ->label('Date')
                    ->date(),
                TextColumn::make('document')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('In or out')
                    ->numeric(maxDecimalPlaces: 3)
                    // A prefix, since formatStateUsing() would replace numeric()'s thousands separators.
                    ->prefix(fn (string $state): ?string => bccomp($state, '0', 3) === 1 ? '+' : null)
                    ->alignEnd(),
                TextColumn::make('unit_cost')
                    ->label('Unit cost')
                    ->money('PHP', decimalPlaces: 4)
                    ->alignEnd(),
                TextColumn::make('on_hand')
                    ->label('On hand after')
                    ->numeric(maxDecimalPlaces: 3)
                    ->alignEnd(),
            ])
            // An item's movements keep growing, so they can be searched (Carbon search).
            ->searchPlaceholder('Search by document');
    }
}
