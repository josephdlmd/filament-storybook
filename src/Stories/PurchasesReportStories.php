<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories\Documents\PurchaseOrder;

class PurchasesReportStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Purchases and payables';
    }

    public function description(): string
    {
        return 'What was bought in a date range (`default`): each posted purchase order with its supplier, the VAT-exclusive amount, the VAT and the total, with a totals row. And what is still owed (`payables`): each order with a balance, its due date and how many days it is past due, oldest first. Built as `reports-sales` is, with its "Download PDF" and "Export" buttons. Basis: the owner\'s business standards (2026-10-12); USWDS table (right-align numbers).';
    }

    public function default(Table $table): Table
    {
        return $table
            ->query(PurchaseOrder::query()->where('status', 'posted'))
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('order_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('supplier')
                    ->searchable(),
                TextColumn::make('vatable_purchases')
                    ->label('VATable purchases')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('vat')
                    ->label('VAT')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('total')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
            ])
            ->defaultSort('order_date')
            ->searchPlaceholder('Search by order or supplier')
            ->filters([
                Filter::make('order_date')
                    ->columnSpan(2)
                    ->schema([
                        Fieldset::make('Order date')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->maxWidth('xs'),
                                DatePicker::make('until')
                                    ->label('To')
                                    ->maxWidth('xs'),
                            ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('order_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('order_date', '<=', $date)))
                    ->indicateUsing(fn (array $data): ?string => filled($data['from'] ?? null) || filled($data['until'] ?? null) ? PrintedDocument::period($data['from'] ?? null, $data['until'] ?? null) : null),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /** What is still owed to suppliers: every order with a balance, oldest due first. */
    public function payables(Table $table): Table
    {
        return $table
            ->query(PurchaseOrder::query()->where('status', 'posted')->where('balance_due', '>', 0))
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('supplier')
                    ->searchable(),
                TextColumn::make('due_date')
                    ->label('Due date')
                    ->date()
                    ->sortable(),
                TextColumn::make('days_past_due')
                    ->label('Days past due')
                    ->state(fn (?Model $record): ?int => $record?->due_date && Carbon::parse($record->due_date)->isPast() ? (int) Carbon::parse($record->due_date)->diffInDays(today()) : null)
                    ->alignEnd(),
                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
            ])
            ->defaultSort('due_date')
            ->searchPlaceholder('Search by order or supplier');
    }
}
