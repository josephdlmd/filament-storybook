<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** A second related list, so Filament shows the two as tabs. */
class SparePartsRelationManager extends PartsRelationManager
{
    protected static ?string $title = 'Spare parts';

    /** Built right, so it raises nothing. */
    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }
}
