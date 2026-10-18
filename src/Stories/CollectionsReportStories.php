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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories\Documents\Payment;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories\Reports\CollectionsReportExporter;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReportExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollectionsReportStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Collections';
    }

    public function description(): string
    {
        return 'The payments received in a date range, by the serial of the receipt each was written on: the cash or check received, the income tax and VAT the customer withheld, with a totals row. A voided payment is left out. Built as `reports-sales` is, with its "Download PDF" and "Export" buttons. Basis: the owner\'s business standards (2026-10-12); USWDS table (right-align numbers).';
    }

    public function examples(): array
    {
        return [CollectionsReportExporter::class, ReportExporter::class];
    }

    public function default(Table $table): Table
    {
        return $table
            ->query(Payment::query()->where('status', DocumentStatus::Posted)->with('salesInvoice.customer'))
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
                    ->label('Invoice')
                    // On a resource: SalesInvoiceResource::getUrl('view', ['record' => $record->salesInvoice])
                    ->url(fn (?Payment $record): ?string => $record?->salesInvoice ? '#'.$record->salesInvoice->number : null),
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
                    ->indicateUsing(fn (array $data): array => array_filter([
                        'from' => filled($data['from'] ?? null) ? PrintedDocument::period($data['from'], null) : null,
                        'until' => filled($data['until'] ?? null) ? PrintedDocument::period(null, $data['until']) : null,
                    ])),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => PrintedDocument::download(PrintedDocument::report(
                        'Collections report',
                        ['Period' => PrintedDocument::period($livewire->getTableFilterState('paid_on')['from'] ?? null, $livewire->getTableFilterState('paid_on')['until'] ?? null)],
                        [
                            'number' => ['label' => 'Receipt'],
                            'paid_on' => ['label' => 'Date', 'format' => 'date'],
                            'salesInvoice.customer.name' => ['label' => 'Customer'],
                            'salesInvoice.number' => ['label' => 'Invoice'],
                            'amount' => ['label' => 'Amount received', 'format' => 'money'],
                            'tax_withheld' => ['label' => 'Income tax withheld', 'format' => 'money'],
                            'vat_withheld' => ['label' => 'VAT withheld', 'format' => 'money'],
                        ],
                        $livewire->getFilteredSortedTableQuery()->get(),
                    ))),
                ExportAction::make()
                    ->exporter(CollectionsReportExporter::class)
                    ->color('gray'),
            ]);
    }
}
