<?php

namespace Josephdlmd\FilamentStorybook\Stories\Imports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SupplierExporter extends Exporter
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('tin')
                ->label('TIN'),
            ExportColumn::make('credit_limit'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = Number::format($export->successful_rows).' '.str('supplier')->plural($export->successful_rows).' exported.';

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '.str('row')->plural($failed).' failed.';
        }

        return $body;
    }
}
