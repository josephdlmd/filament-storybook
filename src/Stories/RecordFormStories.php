<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Attributes\Record;
use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Stories;

class RecordFormStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Record form';
    }

    public function description(): string
    {
        return 'The create and edit form of every resource, pasted into the resource form\'s `configure()`. One column (fields side by side only when they belong together, such as a date range), labels above their fields, no boxed sections, each field as wide as its answer, in the order the record\'s details read. Optional fields say "(optional)" and required ones carry no mark; the app-wide default removes Filament\'s asterisk. A radio group is required, or has a "None" option, since a picked radio can\'t be cleared. The first field takes focus (`autofocus()`); many optional fields go in a section of their own. Save and Cancel stay at the bottom. Build each field from its own pattern (`forms-tin`, `forms-money-input`, `forms-choice`). Basis: Carbon forms 2026 ("Carbon generally recommends single-column forms"; "The pattern used should be consistent throughout your product"; "provide a radio button with the label \'None\'"; the first required input "should receive focus"; "Do not top align primary and secondary action buttons"; up to five options as radios); W3C WAI forms tutorial 2024 (visible labels above their fields); USWDS text input (input width matches the answer).';
    }

    public function records(): array
    {
        return [1 => ['name' => 'Abra Medical Supply', 'tin' => '123-456-789-000', 'credit_limit' => 250000, 'payment_terms' => 'net_30']];
    }

    #[Record]
    #[States('loading', 'error', 'forbidden')]
    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->autofocus()
                    ->maxWidth('md'),
                TextInput::make('tin')
                    ->label('TIN')
                    ->regex('/^[\s.-]*(\d[\s.-]*){12}$/')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? implode('-', str_split(preg_replace('/\D/', '', $state), 3)) : null)
                    ->helperText('For example, 123-456-789-000')
                    ->maxWidth('xs')
                    ->required()
                    ->validationMessages(['regex' => 'Enter a TIN in the correct format: 12 digits, including the branch code']),
                TextInput::make('credit_limit')
                    ->label('Credit limit (optional)')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->rules(['decimal:0,2'])
                    ->maxWidth('xs')
                    ->validationMessages(['decimal' => 'Enter the credit limit with at most 2 decimal places, such as 250000.50']),
                Radio::make('payment_terms')
                    ->options([
                        'cod' => 'Cash on delivery',
                        'net_30' => '30 days',
                        'net_60' => '60 days',
                    ])
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(3),
            ]);
    }
}
