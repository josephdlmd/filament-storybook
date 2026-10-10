<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class RelatedRecordsStories extends Stories
{
    public function title(): string
    {
        return 'Records/Related records';
    }

    public function description(): string
    {
        return 'A list of records related to this one, in a relation manager\'s `table()`, stacked on the View page as `records-view-page` shows. The actions follow what the related records are. Records that belong only to this one, such as a supplier\'s contacts (`HasMany`), are made with New and destroyed with Delete (`default`). Records that exist on their own, such as the users in a team (`BelongsToMany`), are put in with Add and taken out with Remove, whose dialog says the record stays (`linked`); the app-wide defaults label Filament\'s attach and detach actions this way. The list\'s button is gray, so the page keeps its one primary action. Basis: Carbon common actions (Aug 2026: Add, and Remove "is distinct from delete, as a removed item is not destroyed"; "Inform the user if the result is permanent"), Carbon button (Sep 2026: a ghost button for table actions when another button needs primary styling), Carbon data table (Sep 2026: row actions inline when fewer than three).';
    }

    public function records(): array
    {
        return [
            1 => ['name' => 'Jane Cruz', 'email' => 'jane.cruz@example.com'],
            2 => ['name' => 'Paolo Reyes', 'email' => 'paolo.reyes@example.com'],
        ];
    }

    /** Records that belong only to this one: New, Edit and Delete. */
    public function default(Table $table): Table
    {
        return $table
            ->modelLabel('contact')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')
                    ->label('Email address'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('They are removed from this supplier\'s contacts for good. This cannot be undone.'),
            ]);
    }

    /** Records that exist on their own: Add and Remove, and the record stays. */
    public function linked(Table $table): Table
    {
        return $table
            ->modelLabel('user')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')
                    ->label('Email address'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->color('gray'),
            ])
            ->recordActions([
                DetachAction::make()
                    ->modalDescription(fn (Model $record): string => "{$record->name} keeps their account and leaves only this team."),
            ]);
    }
}
