<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Josephdlmd\FilamentStorybook\Attributes\Section;
use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice;

class RecordHistoryStories extends Stories
{
    public function title(): string
    {
        return 'Records/History';
    }

    public function description(): string
    {
        return 'What happened to a document, on its View page: each key event (Created, Sent for approval, Approved, Rejected, Posted, Voided), who did it, when, and the reason where one was asked for (a rejection or a void). Every field change is not kept, only these events. They go in the activity log the app already keeps (spatie/laravel-activitylog), so they also show on its Activity log page. Write one in the same database transaction as the action it records (`records-document-lifecycle`, `actions-approve-and-reject`, `actions-void`): `activity()->performedOn($record)->causedBy(auth()->user())->event(\'voided\')->withProperties([\'reason\' => $data[\'reason\']])->log(\'Voided\')`. Give the document `activities()`, a `morphMany(Activity::class, \'subject\')`, and not spatie\'s `LogsActivity` or `HasActivity`, which log every save. Records are kept at least five years, so never run `activitylog:clean` on a shorter `clean_after_days` than 1826 (business standards). Events are never edited or deleted, so the table has no actions. List them oldest first, as stock movements are (`records-stock`), on the View page below the document\'s details, as the `table()` of a relation manager for `activities` (`records-view-page`). Each time shows its time zone. Business standards from the owner (2026-10-18): key events only, in the activity log. Basis: Carbon data table (Sep 2026: "Tables require three or more columns"), Carbon date picker (Sep 2026: "Specific times should specify a timezone").';
    }

    /** A document's history on its View page, oldest first. */
    #[Section]
    #[States('loading', 'empty')]
    public function default(Table $table): Table
    {
        return $table
            // A relation manager sets this itself.
            ->relationship(fn () => SalesInvoice::find(1)->activities())
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('causer'))
            ->heading('History')
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y g:i A T')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Event'),
                TextColumn::make('causer.name')
                    ->label('By'),
                TextColumn::make('properties.reason')
                    ->label('Reason')
                    ->wrap(),
            ])
            ->emptyStateHeading('No history yet')
            ->emptyStateDescription('What happens to this document, and who did it, appears here.');
    }
}
