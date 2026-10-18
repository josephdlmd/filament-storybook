<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Customer;

class RecordPickerStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Record picker';
    }

    public function description(): string
    {
        return 'Choosing a customer, supplier or item that is a record of its own, such as an invoice\'s customer: a `Select` on the `BelongsTo` relationship, searchable by code and by name, with each option showing both, as in "C-0001 Abra Medical Supply". The list opens with the first options, in code order, and narrows as the user types. Change the relationship, the columns and the label for a supplier or an item; in a line item, `afterStateUpdated()` can copy the item\'s description, unit price and VAT class into the line. On a full-page form whose record may not exist yet, `withAdd` puts a + beside the picker: it opens a slide-over with the record\'s own add form (reuse the resource\'s form fields, cut to what this use needs), and the new record is chosen once saved. `actionSchemaModel()` names the new record\'s model, so its rules never see the page\'s record; show the + only to a user who may create one. Never inside a modal or slide-over form, since one dialog never opens another. A short list that never grows is `forms-choice`, not this. Basis: Carbon dropdown (Sep 2026: "Use a combo box when the user needs to select one option, but the list of options may be very long or not predefined"; "The menu opens by clicking anywhere in the field, allowing users to type and sort through the list of options"; options in alphabetical order; "brief, accurate" option text); Carbon dialogs (Aug 2026: a dialog for "adding a new item to a list where the item details are added and submitted via a dialog", no links that "take the user away from the current context", and "One modal should never trigger another modal"); Carbon forms (Aug 2026: more than five options, a select list).';
    }

    // Not imported, so the snippet's imports stay what a resource needs.
    public function model(): ?string
    {
        return Documents\SalesInvoice::class;
    }

    public function records(): array
    {
        return [1 => ['customer_id' => 3]];
    }

    /** The customer of a sales invoice. */
    public function default(): Select
    {
        return Select::make('customer_id')
            ->relationship('customer', 'name', fn (Builder $query): Builder => $query->orderBy('code'))
            ->getOptionLabelFromRecordUsing(fn (Model $record): string => "{$record->code} {$record->name}")
            ->searchable(['code', 'name'])
            ->preload()
            ->required()
            ->maxWidth('md');
    }

    /** The invoice's customer, with Add customer for one not in the list yet. */
    public function withAdd(): Select
    {
        return Select::make('customer_id')
            ->relationship('customer', 'name', fn (Builder $query): Builder => $query->orderBy('code'))
            ->getOptionLabelFromRecordUsing(fn (Model $record): string => "{$record->code} {$record->name}")
            ->searchable(['code', 'name'])
            ->preload()
            ->required()
            ->maxWidth('md')
            // The resource's own fields; in an app, CustomerResource's form components.
            ->createOptionForm([
                TextInput::make('code')
                    ->helperText('For example, C-0009')
                    ->required()
                    ->maxLength(20)
                    ->maxWidth('xs'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ])
            // The slide-over makes a customer, not the page's invoice, so its rules see no record.
            ->actionSchemaModel(Customer::class)
            ->createOptionUsing(fn (array $data): int => Customer::query()->create($data)->getKey())
            // In an app, also ->visible(fn (): bool => auth()->user()?->can('create', Customer::class) ?? false), which this sample has no policy for.
            ->createOptionAction(fn (Action $action): Action => $action
                ->label('Add customer')
                ->modalHeading('Add customer')
                // Filament labels this button "Create" after the house defaults run; it repeats the action's label.
                ->modalSubmitActionLabel('Add customer')
                ->slideOver());
    }
}
