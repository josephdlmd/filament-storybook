<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Widgets\ChartWidget;

/** Two series with Filament's clickable legend, so the audit has a mouse-only legend to find. */
class GadgetSalesChart extends ChartWidget
{
    protected ?string $heading = 'Gadgets sold';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        return [
            'datasets' => [['label' => 'Online', 'data' => [3, 5]], ['label' => 'In store', 'data' => [2, 4]]],
            'labels' => ['Sep', 'Oct'],
        ];
    }
}
