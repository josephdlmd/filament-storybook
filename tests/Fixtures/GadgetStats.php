<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Keeps Filament's default polling, so the audit has a widget that reloads itself to find. */
class GadgetStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [Stat::make('Gadgets', '3')];
    }
}
