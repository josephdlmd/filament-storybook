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
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Payment;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;

class CollectionsReportStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Collections';
    }

    public function description(): string
    {
        return 'The payments received in a date range, by the serial of the receipt each was written on: the cash or check received, the income tax and VAT the customer withheld, and the total each settled, with a totals row. A voided payment is left out. Built as `reports-sales` is, with its "Download PDF" and "Export" buttons. Basis: the owner\'s business standards (2026-10-12); USWDS table (right-align numbers).';
    }

    public function default(Table $table): Table
    {
        return $table
            ->query(Payment::query()->where('status', 'posted')->with('salesInvoice.customer'))
            ->columns([
                TextColumn::make('number')
                    ->label('Receipt')
                    ->searchable(),
                TextColumn::make('paid_on')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('salesInvoice.customer.name')
                    ->label('Customer'),
                TextColumn::make('salesInvoice.number')
                    ->label('Invoice'),
                TextColumn::make('amount')
                    ->label('Amount received')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('tax_withheld')
                    ->label('Income tax withheld')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('vat_withheld')
                    ->label('VAT withheld')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
            ])
            ->defaultSort('paid_on')
            ->searchPlaceholder('Search by receipt')
            ->filters([
                Filter::make('paid_on')
                    ->columnSpan(2)
                    ->schema([
                        Fieldset::make('Date received')
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
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('paid_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('paid_on', '<=', $date)))
                    ->indicateUsing(fn (array $data): ?string => filled($data['from'] ?? null) || filled($data['until'] ?? null) ? PrintedDocument::period($data['from'] ?? null, $data['until'] ?? null) : null),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->persistFiltersInSession();
    }
}
