<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Keeps Filament's default polling, so the audit has a widget that reloads itself to find. */
class GadgetStats extends StatsOverviewWidget
{
    // A visibility check on a table not migrated yet, which the audit reports instead of stopping.
    public static bool $unmigrated = false;

    public static function canView(): bool
    {
        return static::$unmigrated ? throw new \RuntimeException('no such table: stock') : parent::canView();
    }

    protected function getStats(): array
    {
        return [Stat::make('Gadgets', '3')];
    }
}
