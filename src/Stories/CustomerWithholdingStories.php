<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories\Reports\CustomerWithholding;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReportExporter;
use Josephdlmd\FilamentStorybook\Stories\Reports\SawtExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerWithholdingStories extends Stories
{
    public function title(): string
    {
        return 'Reports/Tax withheld by customers';
    }

    public function description(): string
    {
        return 'The SAWT, the summary of the tax customers withheld from us that goes with each quarter\'s returns (`default`): one row per customer TIN and ATC, with the income payment, the income tax withheld and the VAT withheld in their own columns, and a totals row. Only withholding whose 2307 has arrived is in it, since it is claimed on a return only once the 2307 is in hand; `awaiting` lists the rest, oldest first, to chase before the quarter closes, and `actions-mark-2307-received` records each 2307 as it arrives. Built as `reports-sales` is: a page under Reports, a quarter filter (as of this quarter until the user picks another), "Download PDF" and "Export" (a CSV or Excel file for the CPA to load into BIR\'s alphalist tool, not BIR\'s DAT file, which that tool makes). Quarters go by the payment date. Basis: the owner\'s business standards (2026-10-12: customer withholding claimed only with the 2307; the QAP and SAWT produced for the CPA); RR 2-98 (creditable withholding is supported by the 2307); USWDS table (right-align numbers).';
    }

    public function examples(): array
    {
        return [CustomerWithholding::class, SawtExporter::class, ReportExporter::class];
    }

    public function default(Table $table): Table
    {
        // As of the filter's quarter, or this one. A filter can only add conditions, and the rows are grouped in a subquery,
        // so the table's query reads the quarter itself.
        $quarter = fn (HasTable $livewire): array => [
            (int) ($livewire->getTableFilterState('quarter')['year'] ?? null ?: now()->year),
            (int) ($livewire->getTableFilterState('quarter')['quarter'] ?? null ?: now()->quarter),
        ];

        return $table
            ->query(fn (HasTable $livewire): Builder => CustomerWithholding::sawt(...$quarter($livewire)))
            ->columns([
                TextColumn::make('tin')
                    ->label('TIN')
                    ->searchable(),
                TextColumn::make('customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_atc')
                    ->label('ATC'),
                TextColumn::make('income_payment')
                    ->label('Income payment')
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
            ->defaultSort('customer')
            ->searchPlaceholder('Search by TIN or customer')
            ->filters([
                Filter::make('quarter')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('year')
                            ->options(fn (): array => collect(range(now()->year, now()->year - 2))->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                            ->placeholder('This year'),
                        Select::make('quarter')
                            ->options([1 => 'Q1, January to March', 2 => 'Q2, April to June', 3 => 'Q3, July to September', 4 => 'Q4, October to December'])
                            ->placeholder('This quarter'),
                    ])
                    ->columns(2)
                    ->indicateUsing(fn (array $data): ?string => filled($data['year'] ?? null) || filled($data['quarter'] ?? null)
                        ? 'Q'.($data['quarter'] ?? null ?: now()->quarter).' '.($data['year'] ?? null ?: now()->year)
                        : null),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => PrintedDocument::download(PrintedDocument::report(
                        'Tax withheld by customers',
                        ['Quarter' => 'Q'.$quarter($livewire)[1].' '.$quarter($livewire)[0]],
                        [
                            'tin' => ['label' => 'TIN'],
                            'customer' => ['label' => 'Customer'],
                            'tax_atc' => ['label' => 'ATC'],
                            'income_payment' => ['label' => 'Income payment', 'format' => 'money'],
                            'tax_withheld' => ['label' => 'Income tax withheld', 'format' => 'money'],
                            'vat_withheld' => ['label' => 'VAT withheld', 'format' => 'money'],
                        ],
                        $livewire->getFilteredSortedTableQuery()->get(),
                    ))),
                ExportAction::make()
                    ->exporter(SawtExporter::class)
                    ->color('gray'),
            ]);
    }

    /** Withholding that can't be claimed yet: posted payments whose 2307 hasn't arrived, oldest first. */
    public function awaiting(Table $table): Table
    {
        return $table
            ->query(CustomerWithholding::awaiting()->with('salesInvoice.customer'))
            ->heading('Waiting for a 2307')
            ->columns([
                TextColumn::make('number')
                    ->label('Receipt'),
                TextColumn::make('paid_on')
                    ->label('Paid on')
                    ->date(),
                TextColumn::make('salesInvoice.customer.name')
                    ->label('Customer'),
                TextColumn::make('tax_withheld')
                    ->label('Income tax withheld')
                    ->money('PHP')
                    ->alignEnd(),
                TextColumn::make('vat_withheld')
                    ->label('VAT withheld')
                    ->money('PHP')
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Every 2307 is in')
            ->emptyStateDescription('Payments whose customer withheld tax appear here until their 2307 arrives.');
    }
}
