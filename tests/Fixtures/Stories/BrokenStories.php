<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
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
}
