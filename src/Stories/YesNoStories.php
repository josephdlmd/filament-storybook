<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Checkbox;
use Josephdlmd\FilamentStorybook\Stories;

class YesNoStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Yes or no';
    }

    public function description(): string
    {
        return 'A yes/no saved with the form: a checkbox with a positively worded label, unchecked unless most people would tick it. A toggle is only for something that applies at once, such as a `ToggleColumn`. Basis: Carbon checkbox and toggle (2026), NN/g checkboxes (2024), Carbon forms (defaults).';
    }

    public function default(): Checkbox
    {
        return Checkbox::make('send_invoice_by_email')
            ->label('Send invoice by email');
    }
}
