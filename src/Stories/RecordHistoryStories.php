<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\RecordEvent;
use Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice;

class RecordHistoryStories extends Stories
{
    public function title(): string
    {
        return 'Records/History';
    }

    public function description(): string
    {
        return 'What happened to a document, on its View page: each key event (Created, Sent for approval, Approved, Rejected, Posted, Voided), who did it, when, and the reason where one was asked for (a rejection or a void). Every field change is not kept, only these events. Keep them in a table of their own, `RecordEvent` from the examples, and give each document `events()`, a `morphMany(RecordEvent::class, \'record\')`. Write one in the same database transaction as the action it records (`records-document-lifecycle`, `actions-approve-and-reject`, `actions-void`): `$record->events()->create([\'event\' => \'Voided\', \'user_name\' => auth()->user()->name, \'reason\' => $data[\'reason\'], \'happened_at\' => now()])`. Keep the user\'s name as it was, so the history still reads right after a user is renamed or removed. Events are never edited or deleted, so the table has no actions. List them oldest first, as stock movements are (`records-stock`), on the View page below the document\'s details, as the `table()` of a relation manager for `events` (`records-view-page`). Each time shows its time zone. Business standards from the owner (2026-10-18): key events only, in the app\'s own table, with no activity-log package. Basis: Carbon data table (Sep 2026: "Tables require three or more columns"), Carbon date picker (Sep 2026: "Specific times should specify a timezone").';
    }

    public function examples(): array
    {
        return [RecordEvent::class];
    }

    /** A document's history on its View page, oldest first. */
    public function default(Table $table): Table
    {
        return $table
            ->query(RecordEvent::query()->whereMorphedTo('record', SalesInvoice::find(1)))
            ->heading('History')
            ->defaultSort('happened_at')
            ->columns([
                TextColumn::make('happened_at')
                    ->label('When')
                    ->dateTime('M j, Y g:i A T')
                    ->sortable(),
                TextColumn::make('event'),
                TextColumn::make('user_name')
                    ->label('By'),
                TextColumn::make('reason')
                    ->wrap(),
            ])
            ->emptyStateHeading('No history yet')
            ->emptyStateDescription('What happens to this document, and who did it, appears here.');
    }
}
