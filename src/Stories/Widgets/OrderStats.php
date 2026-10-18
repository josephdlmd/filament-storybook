<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class OrderStats extends StatsOverviewWidget
{
    // No refreshing by itself: content that updates beside other content must be able to stop (WCAG 2.2.2).
    protected ?string $pollingInterval = null;

    // Two or four across, never in thirds, which Filament uses for three figures (Carbon 2x grid: "divide by two").
    protected function getColumns(): int | array | null
    {
        return ['@lg' => 2, '@xl' => 4];
    }

    protected function getStats(): array
    {
        // On a dashboard, count and sum with queries; money stays decimal until it is formatted. They are worked out at most
        // once a minute, not on every load and filter change: the key names what they depend on (the dashboard's filters,
        // and the user when figures follow their permissions), and saving an order may Cache::forget() it.
        $figures = Cache::remember('dashboard.order-stats', 60, fn (): array => [
            'awaiting' => 12,
            'awaitingLastWeek' => 9,
            'approved' => 63,
            'rejected' => 4,
            'spent' => 1250000.50,
        ]);

        // A change has an arrow and says it in words, never by colour alone (Carbon status indicators: a sign, caret or arrow; WCAG 1.4.1).
        return [
            Stat::make('Awaiting approval', Number::format($figures['awaiting']))
                ->description(($figures['awaiting'] - $figures['awaitingLastWeek']).' more than last week')
                ->descriptionIcon(Heroicon::ArrowUp, IconPosition::Before),
            Stat::make('Approved this month', Number::format($figures['approved'])),
            Stat::make('Rejected this month', Number::format($figures['rejected'])),
            Stat::make('Spent this month', Number::currency($figures['spent'], 'PHP')),
        ];
    }
}
