<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Josephdlmd\FilamentStorybook\Stories;

class ChoiceStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Choice';
    }

    public function description(): string
    {
        return 'Picking one option. Up to five options are radios, stacked; more than five, such as provinces, go in a searchable select. Options are in alphabetical or increasing order (or the most used first), each of three words or fewer, with any detail in `descriptions()`. A radio group is required or has a "None" option, since a picked radio can\'t be cleared. Basis: Carbon forms pattern 2026 (more than five options: a select list; groups arranged vertically); Carbon select and dropdown 2026 ("alphabetical order or in increasing order"; option text "should not exceed three words"); Carbon radio button 2026 (an "other" or "none" option); USWDS select 2026 (a select is the "UI of last resort").';
    }

    public function fewOptions(): Radio
    {
        return Radio::make('payment_terms')
            ->options([
                'cod' => 'Cash on delivery',
                'net_30' => '30 days',
                'net_60' => '60 days',
            ])
            ->required();
    }

    /** A long list: searchable, so people type instead of scrolling. */
    public function manyOptions(): Select
    {
        return Select::make('province')
            ->options(['abra' => 'Abra', 'benguet' => 'Benguet', 'cebu' => 'Cebu', 'davao_del_sur' => 'Davao del Sur'])
            ->searchable()
            ->required()
            ->maxWidth('xs');
    }
}
