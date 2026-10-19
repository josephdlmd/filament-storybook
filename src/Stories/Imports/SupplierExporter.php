<?php

namespace Josephdlmd\FilamentStorybook\Stories\Imports;

use Filament\Actions\Exports\ExportColumn;
use Josephdlmd\FilamentStorybook\Reports\ReportExporter;

class SupplierExporter extends ReportExporter
{
    protected static ?string $model = Supplier::class;

    protected static string $title = 'Suppliers';

    protected static string $rows = 'supplier';

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code'),
            ExportColumn::make('name'),
            ExportColumn::make('credit_limit'),
        ];
    }
}
