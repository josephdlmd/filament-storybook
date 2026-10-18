<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Contacts\PhoneNumber;

class SettingsPageStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Settings page';
    }

    public function description(): string
    {
        return 'The settings the whole app shares, such as the company details printed on every document: a Filament `SettingsPage` (the spatie settings plugin), in the menu\'s System group as "Settings", open only to users with the permission to manage settings, and logging each change in the activity log, as the starter\'s General settings page does. Its form is a page form like any other: one column (set with `->columns(1)`, since Filament\'s `SettingsPage` gives its form two after the house default runs), the settings grouped in sections with short headings, each field the same pattern as everywhere else (`forms-tin`, `forms-contact-details`, `forms-choice`, `forms-yes-or-no`), Save at the bottom, a "Saved" toast, and a warning before leaving with unsaved changes (house default). Every setting is on the page, never in a collapsed section or a second tab, since the user checks them against each other. The printout joins the address parts into one line. No source sets rules for settings pages as such; this applies the form rules. Basis: Carbon forms (Aug 2026: single column; buttons at the bottom); Carbon global header (Aug 2026: warn when "state or progress will be lost"); NN/g accordions (Jul 2023); the company\'s TIN, address and phone follow the owner\'s business standards.';
    }

    public function examples(): array
    {
        return [PhoneNumber::class];
    }

    public function records(): array
    {
        return [1 => ['name' => 'Sample Medical Trading Corporation', 'tin' => '123-456-789-000', 'vat' => 'vat', 'top_withholding_agent' => true, 'street' => '12 Rizal Avenue', 'barangay' => 'San Isidro', 'city' => 'Quezon City', 'province' => null, 'zip_code' => '1100', 'phone' => '+63 2 8123 4567', 'email' => 'billing@sample.ph']];
    }

    public function default(Schema $schema): Schema
    {
        return $schema
            // A SettingsPage sets two columns after the house default runs, so its form sets one itself.
            ->columns(1)
            ->components([
                Section::make('Company')
                    ->description('Printed at the top of every document')
                    ->schema([
                        TextInput::make('name')
                            ->label('Registered name')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('tin')
                            ->label('TIN')
                            ->regex('/^[\s.-]*(\d[\s.-]*){12}$/')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? implode('-', str_split(preg_replace('/\D/', '', $state), 3)) : null)
                            ->helperText('For example, 123-456-789-000')
                            ->maxWidth('xs')
                            ->required()
                            ->validationMessages([
                                'required' => 'Enter the TIN',
                                'regex' => 'Enter a TIN in the correct format: 12 digits, including the branch code',
                            ]),
                        Radio::make('vat')
                            ->label('VAT registration')
                            ->options([
                                'vat' => 'VAT-registered',
                                'non_vat' => 'Non-VAT',
                            ])
                            ->required(),
                        Checkbox::make('top_withholding_agent')
                            ->label('Top withholding agent'),
                    ]),
                Section::make('Contact details')
                    ->schema([
                        Fieldset::make('Address')
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
                            ]),
                        TextInput::make('phone')
                            ->label('Phone number')
                            ->tel()
                            ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (filled($value) && PhoneNumber::format($value) === null) {
                                    $fail('Enter a phone number, like 0917 123 4567 or (02) 8123 4567');
                                }
                            }])
                            ->dehydrateStateUsing(fn (?string $state): ?string => PhoneNumber::format($state))
                            ->maxWidth('xs')
                            ->required()
                            ->validationMessages([
                                'required' => 'Enter a phone number',
                            ]),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(254)
                            ->extraInputAttributes(['spellcheck' => 'false'])
                            ->maxWidth('sm')
                            ->required(),
                    ]),
            ]);
    }
}
