<?php

namespace Josephdlmd\FilamentStorybook\Stories\Reports;

use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * What every report's CSV or Excel export shares: the notification when it is ready says how many rows it holds, and how
 * many failed. Each report's exporter sets its model, its columns (as on screen, amounts as plain numbers) and the
 * word for its rows. Copy it into `app/Filament/Exports` with the exporters.
 */
abstract class ReportExporter extends Exporter
{
    /** The rows, as the notification counts them: "invoice", "payment", "customer". */
    protected static string $rows = 'row';

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = Number::format($export->successful_rows).' '.str(static::$rows)->plural($export->successful_rows).' exported.';

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '.str('row')->plural($failed).' failed.';
        }

        return $body;
    }
}
