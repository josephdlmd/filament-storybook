<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Josephdlmd\FilamentStorybook\Stories;

class ContactStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Contact details';
    }

    public function description(): string
    {
        return 'A customer\'s, supplier\'s or contact\'s email address, phone number and address. **Email:** `type="email"` (`email()`), spellcheck off, up to 254 characters, wide enough to show about 30, typed once, never twice to confirm. **Phone:** `type="tel"` (`tel()`), any spaces, dashes or brackets accepted; an app that keeps Philippine numbers checks and stores them as +63 with filament-storybook-ph (`forms-philippine-phone-number`). **Address:** separate fields, since every address is Philippine: street address, barangay (optional), city or municipality, province (optional, as Metro Manila has none) and a 4-digit ZIP code, each as wide as its content; for a billing and a delivery address, prefix the field names. These fields are about someone else, so they set no `autocomplete`, or the browser would offer the user\'s own details (WCAG 1.3.5 is about the user). Basis: GOV.UK email addresses, telephone numbers and addresses patterns (2026), and GOV.UK text input (fixed widths: "phone number inputs should be phone number-sized"); the Philippine address fields are the owner\'s business standards (2026-10-12).';
    }

    public function records(): array
    {
        return [1 => ['email' => 'orders@abramedical.ph', 'phone' => '+63 917 123 4567', 'street' => 'Unit 4, 120 Rizal Avenue', 'barangay' => 'Poblacion', 'city' => 'Bangued', 'province' => 'Abra', 'zip_code' => '2800']];
    }

    public function default(): TextInput
    {
        return TextInput::make('email')
            ->label('Email address')
            ->email()
            ->maxLength(254)
            ->extraInputAttributes(['spellcheck' => 'false'])
            ->maxWidth('sm')
            ->required();
    }

    /** A phone number, typed any way: spaces, dashes and brackets are fine. */
    public function phone(): TextInput
    {
        return TextInput::make('phone')
            ->label('Phone number')
            ->tel()
            ->maxLength(30)
            ->maxWidth('xs')
            ->required()
            ->validationMessages([
                'required' => 'Enter a phone number',
            ]);
    }

    /** A Philippine address, one field per part. */
    public function address(): Fieldset
    {
        return Fieldset::make('Address')
            ->columns(1)
            ->schema([
                TextInput::make('street')
                    ->label('Street address')
                    ->helperText('Unit, building, house number and street')
                    ->maxLength(255)
                    ->required()
                    ->validationMessages(['required' => 'Enter the street address, typically the house number and street']),
                TextInput::make('barangay')
                    ->label('Barangay (optional)')
                    ->maxLength(100)
                    ->maxWidth('sm'),
                TextInput::make('city')
                    ->label('City or municipality')
                    ->maxLength(100)
                    ->maxWidth('sm')
                    ->required(),
                TextInput::make('province')
                    ->label('Province (optional)')
                    ->maxLength(100)
                    ->maxWidth('sm'),
                TextInput::make('zip_code')
                    ->label('ZIP code')
                    ->inputMode('numeric')
                    ->regex('/^\s*\d{4}\s*$/')
                    ->maxWidth('3xs')
                    ->required()
                    ->validationMessages([
                        'required' => 'Enter a ZIP code',
                        'regex' => 'Enter a 4-digit ZIP code',
                    ]),
            ]);
    }
}
