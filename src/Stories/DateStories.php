<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Josephdlmd\FilamentStorybook\Stories;

class DateStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Date';
    }

    public function description(): string
    {
        return 'Every date a user enters: the native date field, which can always be typed into, limited to the dates that make sense. Never `native(false)`: Filament\'s JavaScript picker has a read-only box. Basis: USWDS date picker (2026, "always allow a user to type in the date"), GOV.UK dates pattern.';
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
            ->minDate(today());
    }

    /** A date that has happened, such as an invoice date. */
    public function past(): DatePicker
    {
        return DatePicker::make('invoiced_on')
            ->label('Invoice date')
            ->maxDate(today());
    }
}
