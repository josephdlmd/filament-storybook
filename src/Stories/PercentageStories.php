<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class PercentageStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Percentage';
    }

    public function description(): string
    {
        return 'A percentage the user types, such as a discount or a tax rate: from 0 to 100, up to 2 decimal places, stored as the percentage itself (12.5 for 12.5%, a `decimal(5, 2)` column). The "%" is a suffix, which the house script adds to the field\'s name for screen readers ("Discount, %"), and a "%" the user types too is accepted rather than refused. A text input with the decimal keypad, never `type="number"`. Basis: GOV.UK text input (2026: "Do not rely on prefixes or suffixes alone, because screen readers will not read them out"; users may type the suffix, so "Allow for this in your validation and do not show an error"; `inputmode` decimal); the precision is the owner\'s business standard (2026-10-12).';
    }

    public function records(): array
    {
        return [1 => ['discount' => 12.5]];
    }

    public function default(): TextInput
    {
        return TextInput::make('discount')
            ->label('Discount')
            ->inputMode('decimal')
            ->suffix('%')
            // A "%" typed into the field is dropped, for checking and for saving.
            ->stripCharacters(['%', ' '])
            ->dehydrateStateUsing(fn (string|float|null $state): ?string => filled($state) ? str_replace(['%', ' '], '', (string) $state) : null)
            ->rules(['numeric', 'min:0', 'max:100', 'decimal:0,2'])
            ->helperText('For example, 12.5')
            ->maxWidth('xs')
            ->required()
            ->validationMessages(['decimal' => 'Enter the discount with at most 2 decimal places, such as 12.5']);
    }
}
