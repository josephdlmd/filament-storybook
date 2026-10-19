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
            ExportColumn::make('name'),
            ExportColumn::make('tin')
                ->label('TIN'),
            ExportColumn::make('credit_limit'),
            // A related record's value, by dot notation: the same code the import reads.
            ExportColumn::make('withholdingCode.code')
                ->label('ATC'),
        ];
    }
}
