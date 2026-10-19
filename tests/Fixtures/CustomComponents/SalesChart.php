<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\CustomComponents;

use Filament\Widgets\ChartWidget;

/** Filament's chart widget configured in PHP: allowed. */
class SalesChart extends ChartWidget
{
    protected function getType(): string
    {
        return 'bar';
    }
}
