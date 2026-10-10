<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class MoneyInputStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Money input';
    }

    public function description(): string
    {
        return 'Every peso amount a user types. Decimals are kept and the field is only as wide as the amount.';
    }

    public function records(): array
    {
        return [1 => ['amount' => 1250.5]];
    }

    public function default(): TextInput
    {
        return TextInput::make('amount')
            ->numeric()
            ->prefix('₱')
            ->step(0.01)
            ->minValue(0)
            ->maxWidth('xs');
    }

    /** A required amount says what to do when it is left empty. */
    public function required(): TextInput
    {
        return TextInput::make('amount')
            ->numeric()
            ->prefix('₱')
            ->step(0.01)
            ->minValue(0)
            ->maxWidth('xs')
            ->required()
            ->validationMessages(['required' => 'Enter the amount']);
    }
}
