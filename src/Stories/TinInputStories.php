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
        return 'Every Philippine Tax Identification Number a user types: the 9-digit TIN plus its 3-digit branch code. One field, an example in the hint, any spaces, dashes or dots accepted, and stored as `000-000-000-000`. On a resource form, add `->unique()` so each TIN is registered once. Basis: GOV.UK National Insurance number pattern (one field, an example in the hint, accept and ignore spaces and punctuation, no placeholder); USWDS input mask failed WCAG 2.1 AA in its own tests, so no mask.';
    }

    public function records(): array
    {
        return [1 => ['tin' => '123-456-789-000']];
    }

    public function default(): TextInput
    {
        return TextInput::make('tin')
            ->label('TIN')
            ->regex('/^[\s.-]*(\d[\s.-]*){12}$/')
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? implode('-', str_split(preg_replace('/\D/', '', $state), 3)) : null)
            ->helperText('For example, 123-456-789-000. Head office branch code is 000.')
            ->maxWidth('xs')
            ->validationMessages(['regex' => 'Enter a TIN in the correct format: 12 digits, including the branch code']);
    }

    /** A required TIN says what to do when it is left empty. */
    public function required(): TextInput
    {
        return TextInput::make('tin')
            ->label('TIN')
            ->regex('/^[\s.-]*(\d[\s.-]*){12}$/')
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? implode('-', str_split(preg_replace('/\D/', '', $state), 3)) : null)
            ->helperText('For example, 123-456-789-000. Head office branch code is 000.')
            ->maxWidth('xs')
            ->required()
            ->validationMessages([
                'required' => 'Enter the TIN',
                'regex' => 'Enter a TIN in the correct format: 12 digits, including the branch code',
            ]);
    }
}
