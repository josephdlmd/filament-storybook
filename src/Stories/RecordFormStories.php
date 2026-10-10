<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Stories;

class RecordFormStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Record form';
    }

    public function description(): string
    {
        return 'The create and edit form of every resource, pasted into the resource form\'s `configure()`. One column, labels inline beside their fields, no boxed sections, each field as wide as its answer, in the order the record\'s details read. Build each field from its own pattern (`forms-tin`, `forms-money-input`, `forms-choice`).';
    }

    public function records(): array
    {
        return [1 => ['name' => 'Abra Medical Supply', 'tin' => '123-456-789-000', 'credit_limit' => 250000, 'payment_terms' => 'net_30']];
    }

    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->inlineLabel()
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxWidth('md'),
                TextInput::make('tin')
                    ->label('TIN')
                    ->mask('999-999-999-999')
                    ->regex('/^\d{3}-\d{3}-\d{3}-\d{3}$/')
                    ->helperText('Head office branch code is 000.')
                    ->maxWidth('xs')
                    ->required(),
                TextInput::make('credit_limit')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->maxWidth('xs'),
                ToggleButtons::make('payment_terms')
                    ->options([
                        'cod' => 'Cash on delivery',
                        'net_30' => '30 days',
                        'net_60' => '60 days',
                    ])
                    ->inline(),
            ]);
    }
}
