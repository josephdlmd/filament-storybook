<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class OrderStats extends StatsOverviewWidget
{
    // No refreshing by itself: content that updates beside other content must be able to stop (WCAG 2.2.2).
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // On a dashboard, count and sum with queries; money stays decimal until it is formatted.
        return [
            Stat::make('Awaiting approval', Number::format(12))
                ->description('3 more than last week'),
            Stat::make('Approved this month', Number::format(63)),
            Stat::make('Spent this month', Number::currency(1250000.50, 'PHP')),
        ];
    }
}
