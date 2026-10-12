<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;

/** A screen that breaks every page-level rule storybook-audit checks, so each check is proven to fire. */
class GadgetResource extends Resource
{
    protected static ?string $model = Gadget::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Stock Control';

    // Filament requires an icon on a menu item with items under it (the fixture panel's Stock report).
    protected static string|\BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedCube;

    public static bool $creatable = true;

    public static function canCreate(): bool
    {
        return static::$creatable;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Name')->required(),
            TextInput::make('code')->label('Code:')->placeholder('GAD-0001'),
            TextInput::make('support_email')->label('Support email (optional)'),
            TextInput::make('support_phone')->label('Support phone (optional)'),
            Section::make('Stock Keeping')->collapsible()->schema([TextInput::make('sku')->label('SKU')->readOnly()]),
            TextInput::make('serial')->label('Serial')->hintIcon('heroicon-o-information-circle', tooltip: 'On the back of the gadget')->required()->validationMessages(['required' => 'Please enter the serial']),
            Toggle::make('is_active')->label('Active'),
            \Filament\Forms\Components\RichEditor::make('description')->label('Description (optional)'),
            \Filament\Forms\Components\Radio::make('colour')->label('Colour (optional)')->options(['red' => 'Red', 'blue' => 'Blue'])->inline(),
            Select::make('owner_id')->label('Owner')->relationship('owner', 'name'),
            DatePicker::make('released_on')->label('Released On')->native(false),
            FileUpload::make('manual')->label('Manual'),
            // `part` is built right; `quantity` hides its label under a header that reads differently; `note` has no column.
            Repeater::make('parts')
                ->table([TableColumn::make('Part'), TableColumn::make('Qty')])
                ->schema([TextInput::make('part')->required(), TextInput::make('quantity')->hiddenLabel(), TextInput::make('note')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('name'),
                TextColumn::make('price')->money('PHP')->tooltip('Before VAT'),
                TextColumn::make('created_at')->label('Created At'),
            ])
            ->filters([
                SelectFilter::make('code')->options(['A' => 'A']),
                SelectFilter::make('name')->options(['B' => 'B']),
            ])
            ->recordActions([
                Action::make('ship')->label('Submit'),
                Action::make('returnGadget')->label('Return Gadget'),
                Action::make('archive')->requiresConfirmation(),
                // Shown on no row, so it doesn't count towards the inline actions.
                Action::make('neverShown')->visible(false),
                // Its heading types the record, as apps write it; the audit has no record to give it.
                \Filament\Actions\ActionGroup::make([Action::make('inspect'), Action::make('retire')->modalHeading(fn (Gadget $record): string => "Retire {$record->name}?")]),
            ])
            ->headerActions([
                Action::make('report')->color('gray')->keyBindings(['r']),
                // Title Case that isn't the action's name, as Filament writes it from a capitalised model label.
                Action::make('labels')->label('Print Gadget Labels')->color('gray'),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([DeleteBulkAction::make()->color('danger')->label('Remove selected')]),
            ])
            ->reorderableColumns();
    }

    public static function getRelations(): array
    {
        return [RelationManagers\PartsRelationManager::class, RelationManagers\SparePartsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGadgets::route('/'),
            'create' => Pages\CreateGadget::route('/create'),
            'view' => Pages\ViewGadget::route('/{record}'),
            'edit' => Pages\EditGadget::route('/{record}/edit'),
            'report' => Pages\ReportGadgets::route('/report/{year}'),
        ];
    }
}
