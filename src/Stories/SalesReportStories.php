<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Customer;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReportExporter;
use Josephdlmd\FilamentStorybook\Stories\Reports\SalesReportExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Sales';
    }

    public function description(): string
    {
        return 'The sales report, and the pattern every report follows: a page of its own under a "Reports" menu group, open to roles with the "view reports" permission (`canAccess()`), holding a table of the posted documents in a date range. The filters sit above the table and apply as they change (`tables-list-table`): the date range, and the customer. Money is right-aligned, with the VAT-exclusive amounts, the VAT and the total in their own columns, and a totals row under them that adds every row the filters keep. Two gray buttons take it out: "Download PDF", the shared document layout with the filters as its details, and "Export", Filament\'s CSV or Excel file, made in the background and offered in a notification (`tables-import-and-export`). Make the page a Filament page with `InteractsWithTable`, whose table is this one; copy `SalesReportExporter`. The other reports (`reports-collections`, `reports-receivables-ageing`, `reports-purchases-and-payables`) are built the same way. Basis: the owner\'s business standards (2026-10-12: the four reports, VAT-exclusive amounts, VAT and totals shown apart, on screen, PDF and Excel or CSV, a "view reports" permission); USWDS table (right-align numbers); Carbon data table (Sep 2026: the toolbar holds "global table actions such as … exporting"); Carbon filtering.';
    }

    public function examples(): array
    {
        return [SalesReportExporter::class, ReportExporter::class, PrintedDocument::class];
    }

    public function default(Table $table): Table
    {
        return $table
            ->query(SalesInvoice::query()->where('status', 'posted'))
            ->columns([
                TextColumn::make('number')
                    ->label('Invoice')
                    ->searchable(),
                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('vatable_sales')
                    ->label('VATable sales')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('vat')
                    ->label('VAT')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('zero_rated_sales')
                    ->label('Zero-rated sales')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('exempt_sales')
                    ->label('VAT-exempt sales')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
                TextColumn::make('total')
                    ->money('PHP')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),
            ])
            ->defaultSort('invoice_date')
            ->searchPlaceholder('Search by invoice or customer')
            ->filters([
                Filter::make('invoice_date')
                    ->columnSpan(2)
                    ->schema([
                        Fieldset::make('Invoice date')
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
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('invoice_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('invoice_date', '<=', $date)))
                    ->indicateUsing(fn (array $data): ?string => filled($data['from'] ?? null) || filled($data['until'] ?? null) ? PrintedDocument::period($data['from'] ?? null, $data['until'] ?? null) : null),
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => PrintedDocument::download(PrintedDocument::report(
                        'Sales report',
                        // The filters the user set, so the paper says what it covers.
                        array_filter([
                            'Period' => PrintedDocument::period($livewire->getTableFilterState('invoice_date')['from'] ?? null, $livewire->getTableFilterState('invoice_date')['until'] ?? null),
                            'Customer' => Customer::find($livewire->getTableFilterState('customer')['value'] ?? null)?->name,
                        ]),
                        [
                            'number' => ['label' => 'Invoice'],
                            'invoice_date' => ['label' => 'Date', 'format' => 'date'],
                            'customer.name' => ['label' => 'Customer'],
                            'vatable_sales' => ['label' => 'VATable sales', 'format' => 'money'],
                            'vat' => ['label' => 'VAT', 'format' => 'money'],
                            'zero_rated_sales' => ['label' => 'Zero-rated sales', 'format' => 'money'],
                            'exempt_sales' => ['label' => 'VAT-exempt sales', 'format' => 'money'],
                            'total' => ['label' => 'Total', 'format' => 'money'],
                        ],
                        // Every row the filters keep, not only the page on screen.
                        $livewire->getFilteredSortedTableQuery()->with('customer')->get(),
                    ))),
                ExportAction::make()
                    ->exporter(SalesReportExporter::class)
                    ->color('gray'),
            ]);
    }
}
