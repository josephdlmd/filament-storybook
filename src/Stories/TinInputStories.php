<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Josephdlmd\FilamentStorybook\Stories;

class TinInputStories extends Stories
{
    public function title(): string
    {
        return 'Forms/TIN';
    }

    public function description(): string
    {
        return 'Every Philippine Tax Identification Number a user types: the 9-digit TIN plus its 3-digit branch code, stored as `000-000-000-000`. On a resource form, add `->unique()` so each TIN is registered once. Basis: no placeholder examples (GOV.UK text input, NN/g 2014); the mask is a house choice.';
    }

    public function records(): array
    {
        return [1 => ['tin' => '123-456-789-000']];
    }

    public function default(): TextInput
    {
        return TextInput::make('tin')
            ->label('TIN')
            ->mask('999-999-999-999')
            ->regex('/^\d{3}-\d{3}-\d{3}-\d{3}$/')
            ->helperText('Head office branch code is 000.')
            ->maxWidth('xs')
            ->validationMessages(['regex' => 'Enter all 12 digits: the 9-digit TIN and its 3-digit branch code']);
    }

    /** A required TIN says what to do when it is left empty. */
    public function required(): TextInput
    {
        return TextInput::make('tin')
            ->label('TIN')
            ->mask('999-999-999-999')
            ->regex('/^\d{3}-\d{3}-\d{3}-\d{3}$/')
            ->helperText('Head office branch code is 000.')
            ->maxWidth('xs')
            ->required()
            ->validationMessages([
                'required' => 'Enter the TIN',
                'regex' => 'Enter all 12 digits: the 9-digit TIN and its 3-digit branch code',
            ]);
    }
}
