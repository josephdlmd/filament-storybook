<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use RuntimeException;

class BrokenStories extends Stories
{
    public function title(): string
    {
        return 'Broken/Story';
    }

    public function works(): TextInput
    {
        return TextInput::make('name')->placeholder('Jane Cruz');
    }

    public function throws(): TextInput
    {
        throw new RuntimeException('Story exploded');
    }

    public function untyped()
    {
        return TextInput::make('name');
    }

    public function records(): array
    {
        return [1 => ['code' => 'A-1']];
    }

    public function failsWhileRendering(): TextColumn
    {
        return TextColumn::make('code')
            ->formatStateUsing(fn (string $state): string => $state->upper());
    }

    /** Renders, but a screen reader cannot name the button. */
    public function unnamedIconButton(): Action
    {
        return Action::make('delete')->iconButton()->icon('heroicon-o-trash')->label('');
    }

    /** Renders, but its labels and its notification break the writing rules. */
    public function vagueAndTitleCase(): Action
    {
        return Action::make('save')
            ->label('Submit')
            ->modalHeading('Save Order')
            ->modalDescription('Lorem ipsum dolor sit amet.')
            ->action(fn () => Notification::make()->title('Saved.')->send());
    }

    /** Renders while it has rows, but its row action can't take the null an empty table passes. */
    public function nonNullableRecord(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code')])
            ->recordActions([Action::make('open')->url(fn (Model $record): string => '#'.$record->code)]);
    }

    /** Renders, but sets its own spacing instead of the theme's. */
    public function ownSpacing(): TextInput
    {
        return TextInput::make('code')->extraAttributes(['class' => 'mt-3 px-[13px]'])->extraInputAttributes(['style' => 'padding: 3px']);
    }

    /** Renders, but works out money with floats. */
    public function floatMoney(): TextColumn
    {
        return TextColumn::make('total')
            ->state(fn (Model $record): float => round($record->quantity * $record->unit_price, 2));
    }
}
