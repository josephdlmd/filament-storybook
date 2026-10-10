<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class UnitCostStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Unit cost';
    }

    public function description(): string
    {
        return 'A cost per unit, such as weighted-average or landed cost: up to 4 decimal places, so costs worked out by division don\'t drift by centavos. Line totals made from it are rounded to 2 decimals once, at the end. Business standard from the owner (2026-10-10). Basis for the layout: USWDS and GOV.UK text input.';
    }

    public function records(): array
    {
        return [1 => ['unit_cost' => 89.2857]];
    }

    public function default(): TextInput
    {
        return TextInput::make('unit_cost')
            ->numeric()
            ->prefix('₱')
            ->step(0.0001)
            ->minValue(0)
            ->rules(['decimal:0,4'])
            ->maxWidth('xs')
            ->validationMessages(['decimal' => 'Enter the unit cost with at most 4 decimal places, such as 89.2857']);
    }
}
