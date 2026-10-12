<?php

namespace Josephdlmd\FilamentStorybook\Stories\Reports;

use Filament\Actions\Exports\ExportColumn;
use Josephdlmd\FilamentStorybook\Stories\Documents\Customer;

/** The receivables ageing report as a CSV or Excel file: each customer's buckets as of the report's date. */
class ReceivablesAgeingExporter extends ReportExporter
{
    protected static ?string $model = Customer::class;

    protected static string $rows = 'customer';

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Customer'),
            ...collect([...ReceivablesAgeing::COLUMNS, 'balance_due' => 'Total due'])
                ->map(fn (string $label, string $column): ExportColumn => ExportColumn::make($column)->label($label))
                ->values()
                ->all(),
        ];
    }
}
