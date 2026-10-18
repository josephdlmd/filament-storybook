<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Stories\Reports\ReceivablesAgeing;

/** What customers owe by days past due, as one stacked bar, each bracket's amount in its legend label. */
class ReceivablesAgeingChart extends ChartWidget
{
    protected ?string $heading = 'Owed to us by days past due';

    protected ?string $description = 'The largest overdue amount is over 90 days past due.';

    protected ?string $maxHeight = '140px';

    // No refreshing by itself: content that updates beside other content must be able to stop (WCAG 2.2.2).
    protected ?string $pollingInterval = null;

    /**
     * Gray while current, then warmer and darker with age, each 3:1 on the light and dark backgrounds (WCAG 1.4.11).
     * Neighbours touch at under 3:1, so each legend label carries its amount and the colours never carry it alone.
     */
    protected const COLOURS = ['current' => '#71717a', 'days_1_30' => '#d97706', 'days_31_60' => '#ea580c', 'days_61_90' => '#dc2626', 'over_90' => '#9333ea'];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // On a dashboard, sum the brackets of ReceivablesAgeing::addBuckets(Customer::query(), today()) with one query.
        $owed = ['current' => 11200.00, 'days_1_30' => 3363.71, 'days_31_60' => 890.50, 'days_61_90' => 0.00, 'over_90' => 5000.00];

        return [
            'datasets' => collect(ReceivablesAgeing::COLUMNS)
                ->map(fn (string $label, string $key): array => [
                    'label' => $label.' '.Number::currency($owed[$key], 'PHP'),
                    'data' => [$owed[$key]],
                    'backgroundColor' => static::COLOURS[$key],
                ])
                ->values()
                ->all(),
            'labels' => ['Owed'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            // Several series share one legend, in the same place on every chart, and not clickable (WCAG 2.1.1; Carbon legends).
            'plugins' => ['legend' => ['position' => 'top', 'onClick' => null]],
            'indexAxis' => 'y',
            'scales' => [
                'x' => ['stacked' => true, 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Amount in pesos']],
                'y' => ['stacked' => true, 'display' => false],
            ],
        ];
    }

    /** The values as text, read by screen readers in place of the picture (WCAG 1.1.1). */
    public function getChartAssistiveContent(): string
    {
        return $this->getHeading().': '.collect($this->getCachedData()['datasets'])->pluck('label')->implode(', ').'.';
    }
}
