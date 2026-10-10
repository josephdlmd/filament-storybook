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
        return 'Picking one option. Up to five options are radios; more than five, such as provinces, go in a searchable select. Basis: Carbon forms pattern 2026 (more than five options: a select list); USWDS select 2026 (a select is the "UI of last resort").';
    }

    public function fewOptions(): Radio
    {
        return Radio::make('terms')
            ->options([
                'cod' => 'Cash on delivery',
                'net_30' => '30 days',
                'net_60' => '60 days',
            ]);
    }

    /** A long list: searchable, so people type instead of scrolling. */
    public function manyOptions(): Select
    {
        return Select::make('province')
            ->options(['abra' => 'Abra', 'benguet' => 'Benguet', 'cebu' => 'Cebu', 'davao_del_sur' => 'Davao del Sur'])
            ->searchable();
    }
}
