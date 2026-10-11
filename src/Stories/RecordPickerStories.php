<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class RecordPickerStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Record picker';
    }

    public function description(): string
    {
        return 'Choosing a customer, supplier or item that is a record of its own, such as an invoice\'s customer: a `Select` on the `BelongsTo` relationship, searchable by code and by name, with each option showing both, as in "C-0001 Abra Medical Supply". The list opens with the first options, in code order, and narrows as the user types. Change the relationship, the columns and the label for a supplier or an item; in a line item, `afterStateUpdated()` can copy the item\'s description, unit price and VAT class into the line. A short list that never grows is `forms-choice`, not this. Basis: Carbon dropdown (Sep 2026: "Use a combo box when the user needs to select one option, but the list of options may be very long or not predefined"; "The menu opens by clicking anywhere in the field, allowing users to type and sort through the list of options"; options in alphabetical order; "brief, accurate" option text); Carbon forms (Aug 2026: more than five options, a select list).';
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
}
