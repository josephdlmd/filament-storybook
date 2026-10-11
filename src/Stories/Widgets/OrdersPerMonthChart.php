<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersPerMonthChart extends ChartWidget
{
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
        $months = collect(['2026-05-01', '2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01'])->map(fn (string $month): string => Carbon::parse($month)->translatedFormat('F'));

        return [
            'datasets' => [['label' => 'Approved orders', 'data' => [42, 38, 51, 47, 63]]],
            'labels' => $months->all(),
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
