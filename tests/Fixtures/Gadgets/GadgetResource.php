<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;

/** A screen that breaks every page-level rule storybook-audit checks, so each check is proven to fire. */
class GadgetResource extends Resource
{
    protected static ?string $model = Gadget::class;

    public static bool $creatable = true;

    public static function canCreate(): bool
    {
        return static::$creatable;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Name')->required(),
            TextInput::make('code')->label('Code')->placeholder('GAD-0001'),
            TextInput::make('serial')->label('Serial')->required()->validationMessages(['required' => 'Please enter the serial']),
            Toggle::make('is_active')->label('Active'),
            DatePicker::make('released_on')->label('Released')->native(false),
            FileUpload::make('manual')->label('Manual'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('name'),
                TextColumn::make('price')->money('PHP'),
            ])
            ->filters([
                SelectFilter::make('code')->options(['A' => 'A']),
                SelectFilter::make('name')->options(['B' => 'B']),
            ])
            ->recordActions([
                Action::make('ship'),
                Action::make('return'),
                Action::make('archive'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->color('danger'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGadgets::route('/'),
            'create' => Pages\CreateGadget::route('/create'),
            'edit' => Pages\EditGadget::route('/{record}/edit'),
            'report' => Pages\ReportGadgets::route('/report/{year}'),
        ];
    }
}
