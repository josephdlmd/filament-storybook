<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartsRelationManager extends RelationManager
{
    protected static string $relationship = 'parts';

    public function table(Table $table): Table
    {
        // Breaks the rules a related list can: Title Case, a delete called Remove, and Filament's "Are you sure" text.
        return $table
            ->columns([TextColumn::make('name'), TextColumn::make('created_at')->label('Created At')])
            ->headerActions([CreateAction::make(), \Filament\Actions\Action::make('printParts')->label('Print parts')])
            ->emptyStateActions([\Filament\Actions\Action::make('newPart')->label('New part'), \Filament\Actions\Action::make('importParts')->label('Import parts')])
            ->recordActions([DeleteAction::make()->label('Remove')]);
    }
}
