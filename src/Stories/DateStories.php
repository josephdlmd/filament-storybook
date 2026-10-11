<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Josephdlmd\FilamentStorybook\Stories;

class DateStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Date';
    }

    public function description(): string
    {
        return 'Every date a user enters: the native date field, which can always be typed into, limited to the dates that make sense. Never `native(false)`: Filament\'s JavaScript picker has a read-only box. Basis: Carbon date picker 2026 (a simple way to type dates; min and max dates); USWDS date picker 2026 ("Always allow a user to type in the date manually").';
    }

    public function records(): array
    {
        return [1 => ['due_on' => now()->addDays(30)->toDateString(), 'invoiced_on' => now()->toDateString()]];
    }

    /** A date ahead, such as a due date. */
    public function future(): DatePicker
    {
        return DatePicker::make('due_on')
            ->label('Due date')
            ->maxWidth('xs')
            ->minDate(today());
    }

    /** A date that has happened, such as an invoice date. */
    public function past(): DatePicker
    {
        return DatePicker::make('invoiced_on')
            ->label('Invoice date')
            ->maxWidth('xs')
            ->maxDate(today());
    }

    /** A range: two fields side by side, labelled From and To, in a group named for what the dates are. */
    public function range(): Fieldset
    {
        return Fieldset::make('Invoice dates')
            ->columns(2)
            ->schema([
                DatePicker::make('invoiced_from')
                    ->label('From')
                    ->maxWidth('xs'),
                DatePicker::make('invoiced_until')
                    ->label('To')
                    ->maxWidth('xs')
                    ->afterOrEqual('invoiced_from'),
            ]);
    }
}
