<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Josephdlmd\FilamentStorybook\Stories;

class BulkActionStories extends Stories
{
    public function title(): string
    {
        return 'Tables/Bulk actions';
    }

    public function description(): string
    {
        return 'Doing one thing to many records of a list at once. Ticking a row shows how many are selected, with "Select all" for every record that matches the search and filters, across pages, and "Deselect all"; the header checkbox selects the page and shows a dash when only some of it is. Filament does all of this; the house defaults name each row\'s checkbox after the row ("Select SUP-0001") and keep "Deselect all" out of danger red, since it destroys nothing. The actions are buttons in the table\'s toolbar, shown once a row is ticked, not a "Bulk actions" menu: up to five, the toolbar\'s limit (`tables-list-table`), with Delete selected last. A routine change that can be reversed (Put on hold, Take off hold) runs at once and says so in a toast; a delete confirms in a danger dialog that says the consequence. Each action checks permission per record (`authorizeIndividualRecords()`), so a record the user may not change is skipped and the toast says how many were. Basis: Carbon data table (Sep 2026: "Once an item from the table is selected, the batch action bar appears at the top of the table"; the header checkbox has "three states, check, unchecked, and indeterminate"); NN/g bulk actions (Mar 2025: "provide a Select All option, use a contextual action bar, and give users clear feedback"); NN/g data tables (Apr 2022: "a single-click option to Select All"); Carbon button (Sep 2026: danger is "for actions that could have destructive effects"); Carbon common actions and dialogs (Aug 2026: no confirmation for routine actions); WCAG 2.2 3.3.4, 4.1.3.';
    }

    public function records(): array
    {
        return collect(['Abra Medical Supply', 'Benguet Trading', 'Cebu Office Depot', 'Davao Lab Supplies', 'Eastern Paper Co.', 'Fil-Pharma Distributors', 'General Santos Hardware', 'Hilltop Printing', 'Ilocos Food Service', 'Jade Cleaning Supplies', 'Kalibo Office Furniture', 'Luzon Medical Gases'])
            ->mapWithKeys(fn (string $name, int $index): array => [$index + 1 => [
                'code' => sprintf('SUP-%04d', $index + 1),
                'name' => $name,
                'status' => $index % 4 === 1 ? 'On hold' : 'Active',
            ]])
            ->all();
    }

    public function default(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'On hold' ? 'warning' : 'success'),
            ])
            ->searchPlaceholder('Search by code or name')
            ->filters([
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'On hold' => 'On hold']),
            ])
            // Bulk actions sit straight in the toolbar, as Carbon's batch action bar shows them, not in a BulkActionGroup menu.
            ->toolbarActions([
                // Routine and reversible (Take off hold undoes it): no confirmation. Filament's toast says "Put on hold", or, when the
                // policy refuses some records, "2 of 3 put on hold" and why.
                BulkAction::make('putOnHold')
                    ->label('Put on hold')
                    ->authorizeIndividualRecords('update')
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'On hold']))
                    ->successNotificationTitle('Put on hold')
                    ->failureNotificationTitle(fn (int $successCount, int $totalCount): string => "{$successCount} of {$totalCount} put on hold")
                    ->missingBulkAuthorizationFailureNotificationMessage(fn (int $failureCount): string => "You don't have permission to change {$failureCount}.")
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('takeOffHold')
                    ->label('Take off hold')
                    ->authorizeIndividualRecords('update')
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'Active']))
                    ->successNotificationTitle('Taken off hold')
                    ->failureNotificationTitle(fn (int $successCount, int $totalCount): string => "{$successCount} of {$totalCount} taken off hold")
                    ->missingBulkAuthorizationFailureNotificationMessage(fn (int $failureCount): string => "You don't have permission to change {$failureCount}.")
                    ->deselectRecordsAfterCompletion(),
                // Irreversible: a danger dialog that says the consequence. Filament's toast says "Deleted", or "Deleted 3 of 5" with why.
                DeleteBulkAction::make()
                    ->authorizeIndividualRecords()
                    ->modalDescription('Their contacts and price lists are deleted too. This cannot be undone.'),
            ]);
    }
}
