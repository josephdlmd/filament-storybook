<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Customer;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReceivablesAgeing;

class ReceivablesAgeingStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Receivables ageing';
    }

    public function description(): string
    {
        return 'What each customer owes as of a date, in the ageing buckets by days past the due date (Current, 1–30, 31–60, 61–90, over 90), and the total, with a totals row. It is as of today until the user sets another date. `ReceivablesAgeing` adds the buckets to the customers\' query, so the database adds them up; a customer\'s statement of account (`actions-download-pdf.statementOfAccount`) breaks down one row. Built as `reports-sales` is, with its "Download PDF" and "Export" buttons. Basis: the owner\'s business standards (ageing buckets, 2026-10-11; reports, 2026-10-12); USWDS table (right-align numbers).';
    }

    public function examples(): array
    {
        return [ReceivablesAgeing::class];
    }

    public function default(Table $table): Table
    {
        return $table
            // As of the filter's date, or today. The buckets are columns of the query, so the totals row adds them; a filter
            // can only add conditions, so the query reads the date itself.
            ->query(fn (HasTable $livewire): Builder => ReceivablesAgeing::addBuckets(Customer::query(), Carbon::parse($livewire->getTableFilterState('as_of')['as_of'] ?? today())))
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable(),
                ...collect([...ReceivablesAgeing::COLUMNS, 'balance_due' => 'Total due'])->map(fn (string $label, string $column): TextColumn => TextColumn::make($column)
                    ->label($label)
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')))->values()->all(),
            ])
            ->searchPlaceholder('Search by customer')
            ->filters([
                Filter::make('as_of')
                    ->schema([
                        DatePicker::make('as_of')
                            ->label('As of')
                            ->helperText('Today when left empty')
                            ->maxWidth('xs'),
                    ])
                    ->indicateUsing(fn (array $data): ?string => filled($data['as_of'] ?? null) ? 'As of '.Carbon::parse($data['as_of'])->isoFormat('LL') : null),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false);
    }
}
