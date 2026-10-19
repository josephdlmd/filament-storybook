<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class PartsRelationManager extends RelationManager
{
    protected static string $relationship = 'parts';

    public function table(Table $table): Table
    {
        // Breaks the rules a related list can: Title Case, a delete called Remove, Filament's "Are you sure" text, and a filter
        // without its label, taken out of the table.
        return $table
            ->columns([TextColumn::make('name'), TextColumn::make('created_at')->label('Created At')])
            ->headerActions([CreateAction::make(), \Filament\Actions\Action::make('printParts')->label('Print parts')])
            ->emptyStateActions([\Filament\Actions\Action::make('newPart')->label('New part'), \Filament\Actions\Action::make('importParts')->label('Import parts')])
            ->recordActions([DeleteAction::make()->label('Remove')])
            ->filters([
                Filter::make('year')->schema([Select::make('year')->options([2026 => '2026'])->hiddenLabel()->placeholder('All years')]),
            ], layout: FiltersLayout::Hidden);
    }
}
