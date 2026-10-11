<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Widgets\ChartWidget;

/** One series that keeps Chart.js's legend, so the audit has a needless legend to find. */
class GadgetStockChart extends ChartWidget
{
    protected ?string $heading = 'Gadgets in stock';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return ['datasets' => [['label' => 'In stock', 'data' => [7, 9]]], 'labels' => ['Sep', 'Oct']];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['onClick' => null]]];
    }
}
