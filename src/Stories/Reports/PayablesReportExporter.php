<?php

namespace Josephdlmd\FilamentStorybook\Stories\Reports;

use Filament\Actions\Exports\ExportColumn;
use Josephdlmd\FilamentStorybook\Stories\Documents\PurchaseOrder;

/** What is owed to suppliers as a CSV or Excel file: every order with a balance, its due date and the balance. */
class PayablesReportExporter extends ReportExporter
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string $rows = 'order';

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('number')
                ->label('Order'),
            ExportColumn::make('supplier'),
            ExportColumn::make('due_date')
                ->label('Due date'),
            ExportColumn::make('balance_due')
                ->label('Balance'),
        ];
    }
}
