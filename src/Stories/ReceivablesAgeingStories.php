<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Actions\ExportAction;
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
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReceivablesAgeing;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReceivablesAgeingExporter;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReportExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceivablesAgeingStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Receivables ageing';
    }

    public function description(): string
    {
        return 'What each customer owes as of a date, in the ageing buckets by days past the due date (Current, 1–30, 31–60, 61–90, over 90), and the total, with a totals row. It is as of today until the user sets another date; invoices dated after it are left out, and the balances are today\'s. `ReceivablesAgeing` adds the buckets to the customers\' query, so the database adds them up; a customer\'s statement of account (`actions-download-pdf.statementOfAccount`) breaks down one row. Built as `reports-sales` is, with its "Download PDF" and "Export" buttons. Basis: the owner\'s business standards (ageing buckets, 2026-10-11; reports, 2026-10-12); USWDS table (right-align numbers).';
    }

    public function examples(): array
    {
        return [ReceivablesAgeing::class, ReceivablesAgeingExporter::class, ReportExporter::class];
    }

    public function default(Table $table): Table
    {
        return $table
            ->query(Customer::query())
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
                    // As of the filter's date, or today. The buckets are columns of the query, so the totals row adds them;
                    // baseQuery() adds them even with the filter empty.
                    ->baseQuery(fn (Builder $query, array $data) => ReceivablesAgeing::addBuckets($query, Carbon::parse($data['as_of'] ?? null ?: today())))
                    ->indicateUsing(fn (array $data): ?string => filled($data['as_of'] ?? null) ? 'As of '.Carbon::parse($data['as_of'])->isoFormat('LL') : null),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => PrintedDocument::download(PrintedDocument::report(
                        'Receivables ageing',
                        ['As of' => Carbon::parse($livewire->getTableFilterState('as_of')['as_of'] ?? today())->isoFormat('LL')],
                        [
                            'name' => ['label' => 'Customer'],
                            ...collect([...ReceivablesAgeing::COLUMNS, 'balance_due' => 'Total due'])->map(fn (string $label): array => ['label' => $label, 'format' => 'money'])->all(),
                        ],
                        $livewire->getFilteredSortedTableQuery()->get(),
                    ))),
                ExportAction::make()
                    ->exporter(ReceivablesAgeingExporter::class)
                    ->color('gray'),
            ]);
    }
}
