<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
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

    /** With none in the range, the description says why the bars are empty and what would fill them (Carbon empty states). */
    public function getDescription(): ?string
    {
        if (array_sum($this->getCachedData()['datasets'][0]['data']) > 0) {
            return $this->description;
        }

        return 'No orders were approved in this period. Approved orders appear here by the month they were approved.';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // On a dashboard, the counts come from one query grouped by month, which leaves out the months with none, such as
        // Order::approved()->whereBetween('approved_on', [$from, $until])->selectRaw("date_trunc('month', approved_on)::date as month, count(*) as count")->groupBy('month')->pluck('count', 'month').
        $counts = collect(['2026-05-01' => 42, '2026-06-01' => 38, '2026-07-01' => 51, '2026-08-01' => 47, '2026-09-01' => 63]);

        // Every month in the range is listed: 0 where there were none, null where the count is unknown, never a month left
        // out (Carbon axes: "Never change axis ticks increments to accommodate data availability"). The dashboard's From
        // and To set the range. Month names follow the app's locale.
        $from = filled($this->pageFilters['from'] ?? null) ? Carbon::parse($this->pageFilters['from'])->startOfMonth() : Carbon::parse($counts->keys()->first());
        $until = filled($this->pageFilters['until'] ?? null) ? Carbon::parse($this->pageFilters['until'])->startOfMonth() : Carbon::parse($counts->keys()->last());
        $months = collect(CarbonPeriod::create($from, '1 month', $until));

        return [
            'datasets' => [['label' => 'Approved orders', 'data' => $months->map(fn (CarbonInterface $month): int => $counts[$month->toDateString()] ?? 0)->all()]],
            'labels' => $months->map(fn (CarbonInterface $month): string => $month->translatedFormat('F'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            // One series needs no legend; a bar chart's value axis starts at zero and says what it counts (Carbon legends, axes).
            'plugins' => ['legend' => ['display' => false]],
            // Orders are counted whole, so the axis never steps in tenths, even with none.
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'title' => ['display' => true, 'text' => 'Orders']]],
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
