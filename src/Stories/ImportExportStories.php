<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Imports\SupplierExporter;
use Josephdlmd\FilamentStorybook\Stories\Imports\SupplierImporter;

class ImportExportStories extends Stories
{
    public function title(): string
    {
        return 'Tables/Import and export';
    }

    public function description(): string
    {
        return 'Bringing records in from a CSV file and taking them out, from the List page: put both in its `getHeaderActions()`, gray beside the primary New button. An import says what it accepts before the upload: Filament\'s modal offers an example file built from each column\'s `example()`, and `maxRows()` sets the most rows, which the modal states before the upload (an app-wide default). Each row is checked with the same rules and errors as the form, on the server, and a related record is found by the value people know it by, never an id (`relationship()` on the import column, dot notation on the export column; the supplier\'s ATC below); rows that fail are skipped, and the notification when it finishes says how many and offers the errors to download, fix and import again. Imports and exports run as queued jobs without policy checks, so authorize the action and decide in `resolveRecord()` which records an import may change. The importer and exporter below are the pattern for yours (`app/Filament/Imports`, `app/Filament/Exports`). Setup, once per app: `php artisan make:notifications-table`, `php artisan vendor:publish --tag=filament-actions-migrations`, `php artisan migrate`, `->databaseNotifications()` on the panel, and a queue worker (`composer dev` runs one). Basis: Carbon data table (Sep 2026: the toolbar holds "global table actions such as … exporting"), Carbon button (a ghost button when another needs primary styling), USWDS file input (state limits up front), Carbon notifications (Aug 2026: errors say what to do next).';
    }

    public function examples(): array
    {
        return [SupplierImporter::class, SupplierExporter::class];
    }

    /** Import: a CSV file whose columns are mapped, with an example to download first. */
    public function default(): ImportAction
    {
        return ImportAction::make()
            ->importer(SupplierImporter::class)
            ->maxRows(5000)
            ->color('gray');
    }

    /** Export: the columns and the format (CSV or Excel) are chosen in the modal; the file arrives in a notification. */
    public function export(): ExportAction
    {
        return ExportAction::make()
            ->exporter(SupplierExporter::class)
            ->color('gray');
    }
}
