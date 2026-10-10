<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
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
        return 'The create and edit form of every resource, pasted into the resource form\'s `configure()`. One column, labels above their fields, no boxed sections, each field as wide as its answer, in the order the record\'s details read. Build each field from its own pattern (`forms-tin`, `forms-money-input`, `forms-choice`). Basis: W3C WAI forms tutorial 2024 (visible labels above their fields); Carbon forms 2026 (mark the smaller group, so optional fields say "(optional)"; up to five options as radios); USWDS text input (input width matches the answer).';
    }

    public function records(): array
    {
        return [1 => ['name' => 'Abra Medical Supply', 'tin' => '123-456-789-000', 'credit_limit' => 250000, 'payment_terms' => 'net_30']];
    }

    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxWidth('md'),
                TextInput::make('tin')
                    ->label('TIN')
                    ->regex('/^[\s.-]*(\d[\s.-]*){12}$/')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? implode('-', str_split(preg_replace('/\D/', '', $state), 3)) : null)
                    ->helperText('For example, 123-456-789-000, with 000 for the head office')
                    ->maxWidth('xs')
                    ->required(),
                TextInput::make('credit_limit')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->maxWidth('xs'),
                Radio::make('payment_terms')
                    ->options([
                        'cod' => 'Cash on delivery',
                        'net_30' => '30 days',
                        'net_60' => '60 days',
                    ]),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(3),
            ]);
    }
}
