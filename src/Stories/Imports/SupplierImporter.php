<?php

namespace Josephdlmd\FilamentStorybook\Stories\Imports;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class SupplierImporter extends Importer
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            // The supplier's code, which a row matches on, as people know it.
            ImportColumn::make('code')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : $state)
                ->rules(['required', 'max:20'])
                ->example('SUP-0001'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Abra Medical Supply'),
            // Kept as text, never numeric(), which rounds a third decimal away instead of rejecting it.
            ImportColumn::make('credit_limit')
                ->rules(['nullable', 'numeric', 'decimal:0,2', 'min:0'])
                ->example('250000.00'),
        ];
    }

    /** Errors say what to do, as the form's do. */
    public function getValidationMessages(): array
    {
        return [
            'code.required' => 'Enter the code',
            'name.required' => 'Enter the name',
            'credit_limit.numeric' => 'Enter the credit limit as a number, such as 250000.50',
            'credit_limit.decimal' => 'Enter the credit limit with at most 2 decimal places, such as 250000.50',
            'credit_limit.min' => 'Enter a credit limit of 0 or more',
        ];
    }

    /** A row whose code is on file updates that supplier; any other row adds one. Imports run without policy checks. */
    public function resolveRecord(): Supplier
    {
        return Supplier::firstOrNew(['code' => $this->data['code']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = Number::format($import->successful_rows).' '.str('supplier')->plural($import->successful_rows).' imported.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '.str('row')->plural($failed).' failed: download the errors, fix those rows and import them again.';
        }

        return $body;
    }
}
