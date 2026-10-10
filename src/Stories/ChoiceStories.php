<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Josephdlmd\FilamentStorybook\Stories;

class ChoiceStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Choice';
    }

    public function description(): string
    {
        return 'Picking one option. ToggleButtons up to 5 options, Select up to 15, searchable Select beyond that.';
    }

    public function fewOptions(): ToggleButtons
    {
        return ToggleButtons::make('terms')
            ->options([
                'cod' => 'Cash on delivery',
                'net_30' => '30 days',
                'net_60' => '60 days',
            ])
            ->inline();
    }

    /** Over 15 options: searchable, so users type instead of scrolling. */
    public function manyOptions(): Select
    {
        return Select::make('province')
            ->options(['abra' => 'Abra', 'benguet' => 'Benguet', 'cebu' => 'Cebu', 'davao_del_sur' => 'Davao del Sur'])
            ->searchable();
    }
}
