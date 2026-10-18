<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\RecordNote;
use Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice;

class RecordNotesStories extends Stories
{
    public function title(): string
    {
        return 'Records/Notes';
    }

    public function description(): string
    {
        return 'Notes people write on a record, such as what a customer asked for or when a check was promised, on its View page below the details, as the `table()` of a relation manager for `notes` (`records-view-page`). A note isn\'t part of the document\'s figures, so a posted document takes notes too (`records-locked`). Keep them in a table of their own, `RecordNote` from the examples, and give each record `notes()`, a `morphMany(RecordNote::class, \'record\')`. A note belongs only to its record, so it is made with New note and destroyed with Delete (`records-related-records`); add a policy whose `update()` and `delete()` let only the person who wrote one (`user_id`) edit or delete it (the canvas has no policy, so the story leaves it out). List them oldest first, each with who wrote it and when, the time with its zone. The note field states its limit before the user types. Business standards from the owner (2026-10-18): up to 1,000 characters; only a note\'s writer may edit or delete it. Basis: the related-list rule (Carbon common actions, Aug 2026), Carbon button (Sep 2026: a ghost button for table actions when another button needs primary styling), Carbon number input (Sep 2026: allowable input in helper text), Carbon date picker (Sep 2026: "Specific times should specify a timezone").';
    }

    public function examples(): array
    {
        return [RecordNote::class];
    }

    /** A record's notes on its View page, oldest first, with New note, Edit and Delete. */
    public function default(Table $table): Table
    {
        $note = [
            Textarea::make('body')
                ->label('Note')
                ->helperText('Up to 1,000 characters')
                ->required()
                ->maxLength(1000)
                ->rows(4),
        ];

        return $table
            // A relation manager sets this itself.
            ->relationship(fn () => SalesInvoice::find(2)->notes())
            ->heading('Notes')
            ->modelLabel('note')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('written_at')
            ->columns([
                TextColumn::make('body')
                    ->label('Note')
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('By'),
                TextColumn::make('written_at')
                    ->label('When')
                    ->dateTime('M j, Y g:i A T')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->color('gray')
                    ->schema($note)
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'user_id' => auth()->id(), 'written_at' => now()]),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($note),
                DeleteAction::make()
                    ->modalDescription('The note is deleted. This cannot be undone.'),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Notes anyone writes on this record appear here.');
    }
}
