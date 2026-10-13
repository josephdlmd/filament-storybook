<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class OrdersPerMonthChart extends ChartWidget
{
    // Reads the dashboard's filter (`Dashboard`) as $this->pageFilters, and redraws when it changes.
    use InteractsWithPageFilters;

    protected ?string $heading = 'Approved orders per month';

    protected ?string $description = 'September had the most this year.';

    // No refreshing by itself: content that updates beside other content must be able to stop (WCAG 2.2.2).
    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // On a dashboard, count approved orders per month with a query, and list every month in the range: 0 where there
        // were none, null where the count is unknown, never a month left out (Carbon axes: "Never change axis ticks
        // increments to accommodate data availability"). Month names follow the app's locale.
        // The dashboard's From and To narrow the months, as a whereBetween() on the query would.
        $from = filled($this->pageFilters['from'] ?? null) ? Carbon::parse($this->pageFilters['from'])->startOfMonth() : null;
        $until = filled($this->pageFilters['until'] ?? null) ? Carbon::parse($this->pageFilters['until']) : null;
        $orders = collect(['2026-05-01' => 42, '2026-06-01' => 38, '2026-07-01' => 51, '2026-08-01' => 47, '2026-09-01' => 63])
            ->filter(fn (int $count, string $month): bool => (! $from || $from->lte($month)) && (! $until || $until->gte($month)));

        return [
            'datasets' => [['label' => 'Approved orders', 'data' => $orders->values()->all()]],
            'labels' => $orders->keys()->map(fn (string $month): string => Carbon::parse($month)->translatedFormat('F'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            // One series needs no legend; a bar chart's value axis starts at zero and says what it counts (Carbon legends, axes).
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Orders']]],
        ];
    }

    /** The values as text, read by screen readers in place of the picture (WCAG 1.1.1). */
    public function getChartAssistiveContent(): string
    {
        $data = $this->getCachedData();

        return $this->getHeading().': '.collect($data['labels'])
            ->map(fn (string $label, int $i): string => "{$label} {$data['datasets'][0]['data'][$i]}")
            ->implode(', ').'.';
    }
}
