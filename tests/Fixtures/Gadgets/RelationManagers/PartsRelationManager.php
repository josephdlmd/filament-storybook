<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartsRelationManager extends RelationManager
{
    protected static string $relationship = 'parts';

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }
}
