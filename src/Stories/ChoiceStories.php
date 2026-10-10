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
        return 'Picking one option. Show the options as radios when there are few enough to read at a glance; only a long list, such as provinces, goes in a searchable select. Basis: GOV.UK select (a select is a last resort, because users in research could not close or scroll one) and GOV.UK radios.';
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
