<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Customer;

class ArchiveStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Archive';
    }

    public function description(): string
    {
        return 'Taking a customer, supplier or item out of use (a customer here). One that appears on a posted document is archived, never destroyed: it leaves the list and the pickers but stays on its documents and reports, an Archived filter shows it again, and Restore brings it back. One never used on a document can be deleted for good, after a danger confirmation. In Filament, archiving is a soft delete: give the model `SoftDeletes`, add an "Archive" action that soft-deletes (gray, since it can be undone, so no confirmation; Filament\'s `DeleteAction` always confirms), `ForceDeleteAction` labelled "Delete" for unused records, `RestoreAction`, and `TrashedFilter` labelled "Archived". The resource\'s `getRecordRouteBindingEloquentQuery()` keeps archived records openable, and pickers leave them out (`relationship(modifyQueryUsing:)` without trashed records, Filament\'s default). Basis: the owner\'s business standards (records are kept at least five years; used records are archived, unused ones may be deleted, 2026-10-12); Carbon common actions (Aug 2026: delete "destroys an object", confirm when "the data cannot be recreated easily"; low-impact actions need no confirmation); Carbon button (danger only for destructive actions).';
    }

    public function model(): ?string
    {
        return Customer::class;
    }

    public function records(): array
    {
        return [
            1 => ['code' => 'C-0001', 'name' => 'Abra Medical Supply', 'used' => true, 'deleted_at' => null],
            2 => ['code' => 'C-0002', 'name' => 'Bicol Health Centre', 'used' => false, 'deleted_at' => null],
            3 => ['code' => 'C-0003', 'name' => 'Cebu Diagnostics Laboratory', 'used' => true, 'deleted_at' => '2026-09-30 10:00:00'],
        ];
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
                TextColumn::make('deleted_at')
                    ->label('Archived on')
                    ->date(),
            ])
            ->searchPlaceholder('Search by code or name')
            ->filters([
                TrashedFilter::make()
                    ->label('Archived')
                    ->placeholder('Without archived')
                    ->trueLabel('With archived')
                    ->falseLabel('Only archived'),
            ])
            ->recordActions([
                // Used on a posted document: archived, so it can be restored. On a resource, `used` is a check such as
                // $record->salesInvoices()->whereNotNull('number')->exists(), a posted invoice having its number.
                Action::make('archive')
                    ->label('Archive')
                    ->color('gray')
                    ->authorize('delete')
                    ->visible(fn (?Model $record): bool => (bool) $record?->used && ! $record?->trashed())
                    ->action(function (Model $record, Action $action): void {
                        $record->delete();
                        $action->success();
                    })
                    ->successNotificationTitle('Customer archived'),
                // Never used: deleted for good, after a danger confirmation.
                ForceDeleteAction::make()
                    ->label('Delete')
                    ->outlined()
                    ->modalHeading(fn (?Model $record): string => "Delete customer {$record?->name}?")
                    ->modalDescription('It has never been used on a document. This cannot be undone.')
                    ->visible(fn (?Model $record): bool => ! $record?->used && ! $record?->trashed()),
                RestoreAction::make(),
            ]);
    }
}
