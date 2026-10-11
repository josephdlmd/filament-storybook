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
        return 'Every peso amount a user types: pesos and centavos, at most 2 decimal places, checked on the server. The currency sits in a prefix and the field is only as wide as the amount. Business standard from the owner (2026-10-10). Basis for the layout: USWDS and GOV.UK text input (input width matches the expected answer).';
    }

    public function records(): array
    {
        return [1 => ['amount' => 1250.5]];
    }

    public function default(): TextInput
    {
        return TextInput::make('amount')
            ->label('Amount (optional)')
            ->numeric()
            ->prefix('₱')
            ->step(0.01)
            ->minValue(0)
            ->rules(['decimal:0,2'])
            ->maxWidth('xs')
            ->validationMessages(['decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50']);
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
            ->rules(['decimal:0,2'])
            ->required()
            ->validationMessages([
                'required' => 'Enter the amount',
                'decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50',
            ]);
    }
}
