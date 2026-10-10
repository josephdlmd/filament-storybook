<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;

/** A screen that breaks every page-level house rule, for storybook-audit to find. */
class GadgetResource extends Resource
{
    protected static ?string $model = Gadget::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Name'),
            TextInput::make('code')->label('Code')->placeholder('GAD-0001'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code'),
            TextColumn::make('name'),
            TextColumn::make('price')->money('PHP'),
            ...array_map(fn (int $i): TextColumn => TextColumn::make("extra_{$i}"), range(1, 6)),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGadgets::route('/'),
            'create' => Pages\CreateGadget::route('/create'),
            'edit' => Pages\EditGadget::route('/{record}/edit'),
        ];
    }
}
