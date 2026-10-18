<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class FormContainerStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Form container';
    }

    public function description(): string
    {
        return 'Where a form goes, by its size and use. A short, infrequent task with fewer than five inputs goes in a modal (`default`). More than five inputs, or a task repeated while looking at the information it changes, goes in a slide-over (`slideOver`). A long, complex or multi-step task gets its own page; when it is a linear task of three or more steps, a wizard (`wizard`). Exactly five is left to judgment. No tabs or collapsed sections in a modal or slide-over. A modal or slide-over focuses its first field, keeps the user\'s input when they click outside it (an app-wide default), and stays open with the errors marked when validation fails. In a wizard, each step is named in one or two words saying what it does, is checked before the next, and Back returns to review; a wizard that commits the business to something, such as an order, payment or contract, ends with "Check answers", each answer under its question before the final button, and an answer is changed from its step in the wizard\'s header, which keeps what was typed; what an earlier step already answers fills the later one (the supplier\'s own payment terms), so it isn\'t asked again; on a Create page use `HasWizard` and return the steps from `getSteps()`, where the last button is "Create". A step with item lines uses `forms-line-items`. Basis: Carbon forms (Aug 2026: dialog under five inputs, side panel over five; multistep forms), Carbon modal (Sep 2026: short, non-frequent tasks; focus, validation; "the Next button label should change to reflect the final action"), Carbon dialogs (Aug 2026: no complex forms; no tabs or accordions), Carbon progress indicator (Sep 2026: three or more linear steps; labels); WCAG 3.3.7 (Sep 2025: information already provided is "auto-populated, or available for the user to select"); WCAG 3.3.4 (a submission with legal or financial commitments offers "reviewing, confirming, and correcting information before finalizing"); Carbon forms (Aug 2026: users "return to a previous step to review"); GOV.UK check answers (its name, and after a change users "should not need to go through the rest of the transaction again").';
    }

    public function records(): array
    {
        return [1 => [
            'name' => 'Cebu Diagnostics Laboratory',
            'credit_limit' => '250000.00',
            'contact_name' => 'Maria Santos',
            'contact_phone' => '+63 917 123 4567',
            'address' => '12 Osmeña Boulevard',
            'city' => 'Cebu City',
            'province' => 'cebu',
        ]];
    }

    /** Fewer than five inputs, done now and then: a modal. */
    public function default(): Action
    {
        return Action::make('changeCreditLimit')
            ->label('Change credit limit')
            // A custom action starts empty; EditAction is the only one that fills itself from the record.
            ->fillForm(fn (?Model $record): array => $record?->only(['credit_limit']) ?? [])
            ->schema([
                TextInput::make('credit_limit')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->rules(['decimal:0,2'])
                    ->required()
                    ->maxWidth('xs')
                    ->validationMessages(['decimal' => 'Enter the credit limit with at most 2 decimal places, such as 250000.00']),
                Textarea::make('note')
                    ->label('Note (optional)')
                    ->rows(3),
            ])
            ->successNotificationTitle(fn (?Model $record): string => "Credit limit of {$record?->name} changed")
            ->action(fn (Action $action) => $action->success());
    }

    /** More than five inputs, or a task repeated while looking at the record: a slide-over. */
    public function slideOver(): Action
    {
        return Action::make('editDelivery')
            ->label('Edit delivery details')
            ->slideOver()
            ->fillForm(fn (?Model $record): array => $record?->only(['contact_name', 'contact_phone', 'address', 'city', 'province', 'instructions']) ?? [])
            ->schema([
                TextInput::make('contact_name')
                    ->required()
                    ->maxWidth('md'),
                TextInput::make('contact_phone')
                    ->tel()
                    ->required()
                    ->maxWidth('xs'),
                Textarea::make('address')
                    ->required()
                    ->rows(3),
                TextInput::make('city')
                    ->required()
                    ->maxWidth('md'),
                Select::make('province')
                    ->options(['abra' => 'Abra', 'benguet' => 'Benguet', 'cebu' => 'Cebu', 'davao_del_sur' => 'Davao del Sur', 'ilocos_norte' => 'Ilocos Norte', 'laguna' => 'Laguna', 'pampanga' => 'Pampanga'])
                    ->searchable()
                    ->required()
                    ->maxWidth('xs'),
                Textarea::make('instructions')
                    ->label('Instructions (optional)')
                    ->rows(3),
            ])
            ->successNotificationTitle(fn (?Model $record): string => "Delivery details of {$record?->name} saved")
            ->action(fn (Action $action) => $action->success());
    }

    /** A linear task of three or more steps: a wizard on its own page, each step named for what it does, ending with the answers to check. */
    public function wizard(Schema $schema): Schema
    {
        // On a resource, the supplier's name comes from its record; the terms are the field's own options.
        $suppliers = [1 => 'SUP-0001 Abra Medical Supply', 2 => 'SUP-0002 Benguet Trading'];
        $terms = ['cod' => 'Cash on delivery', 'net_30' => '30 days', 'net_60' => '60 days'];

        return $schema
            ->components([
                Wizard::make([
                    Step::make('Choose supplier')
                        ->schema([
                            Select::make('supplier_id')
                                ->label('Supplier')
                                // On a resource, the record picker (`forms-record-picker`): the relationship, searched by code and name.
                                ->options($suppliers)
                                ->searchable()
                                ->required()
                                ->maxWidth('md')
                                // The supplier's own terms fill the next step, so they aren't asked again (WCAG 3.3.7);
                                // on a resource, read them from the supplier record.
                                ->live()
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('payment_terms', [1 => 'net_30', 2 => 'cod'][$state] ?? null)),
                        ]),
                    Step::make('Set terms')
                        ->schema([
                            Radio::make('payment_terms')
                                ->options($terms)
                                ->required(),
                        ]),
                    Step::make('Set delivery')
                        ->schema([
                            DatePicker::make('delivery_date')
                                ->maxWidth('xs')
                                ->required(),
                        ]),
                    // Each answer under its question, read from the steps; an answer is changed from its step in the header
                    // or with Back, which keep what was typed. Entries, so nothing here is saved twice.
                    Step::make('Check answers')
                        ->schema([
                            TextEntry::make('supplier_answer')
                                ->label('Supplier')
                                ->state(fn (Get $get): ?string => $suppliers[$get('supplier_id')] ?? null),
                            TextEntry::make('payment_terms_answer')
                                ->label('Payment terms')
                                ->state(fn (Get $get): ?string => $terms[$get('payment_terms')] ?? null),
                            TextEntry::make('delivery_date_answer')
                                ->label('Delivery date')
                                ->state(fn (Get $get): ?string => $get('delivery_date'))
                                ->date(),
                        ]),
                ]),
            ]);
    }
}
