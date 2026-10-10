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
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Abra Medical Supply'),
            // The same check and stored format as the form's TIN field (forms-tin).
            ImportColumn::make('tin')
                ->label('TIN')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? implode('-', str_split(preg_replace('/\D/', '', $state), 3)) : null)
                ->rules(['required', 'regex:/^\d{3}-\d{3}-\d{3}-\d{3}$/'])
                ->example('123-456-789-000'),
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
            'name.required' => 'Enter the name',
            'tin.required' => 'Enter the TIN',
            'tin.regex' => 'Enter the TIN as 12 digits, such as 123-456-789-000',
            'credit_limit.numeric' => 'Enter the credit limit as a number, such as 250000.50',
            'credit_limit.decimal' => 'Enter the credit limit with at most 2 decimal places, such as 250000.50',
            'credit_limit.min' => 'Enter a credit limit of 0 or more',
        ];
    }

    /** A row whose TIN is on file updates that supplier; any other row adds one. Imports run without policy checks. */
    public function resolveRecord(): Supplier
    {
        return Supplier::firstOrNew(['tin' => $this->data['tin']]);
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
