<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class QuantityStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Quantity';
    }

    public function description(): string
    {
        return 'A quantity, including weights, volumes and lengths: up to 3 decimal places, with the unit in a suffix. Business standard from the owner (2026-10-10). Basis for the layout: USWDS and GOV.UK text input.';
    }

    public function records(): array
    {
        return [1 => ['quantity' => 12.5]];
    }

    public function default(): TextInput
    {
        return TextInput::make('quantity')
            ->numeric()
            ->step(0.001)
            ->minValue(0)
            ->rules(['decimal:0,3'])
            ->suffix('kg')
            ->helperText('Up to 3 decimal places')
            ->maxWidth('xs')
            ->required()
            ->validationMessages(['decimal' => 'Enter the quantity with at most 3 decimal places, such as 12.5']);
    }
}
