<?php

namespace Josephdlmd\FilamentStorybook\Stories\Reports;

use Filament\Actions\Exports\ExportColumn;
use Josephdlmd\FilamentStorybook\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Reports\ReportExporter;
use Josephdlmd\FilamentStorybook\Stories\Documents\PurchaseOrder;

/** What is owed to suppliers as a CSV or Excel file: every order with a balance, its due date, the days past it and the balance. */
class PayablesReportExporter extends ReportExporter
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string $title = 'Payables';

    protected static string $rows = 'order';

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('number')
                ->label('Order'),
            ExportColumn::make('supplier'),
            ExportColumn::make('due_date')
                ->label('Due date'),
            ExportColumn::make('days_past_due')
                ->label('Days past due')
                ->state(fn (PurchaseOrder $record): ?int => PrintedDocument::daysPastDue($record->due_date)),
            ExportColumn::make('balance_due')
                ->label('Balance'),
        ];
    }
}
