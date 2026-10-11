<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class CashFlowChart extends ChartWidget
{
    protected ?string $heading = 'Collected and paid per month';

    protected ?string $description = 'Collections passed payments from July.';

    // No refreshing by itself: content that updates beside other content must be able to stop (WCAG 2.2.2).
    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // On a dashboard, sum each month's collections and payments with queries, listing every month in the range.
        $months = collect(['2026-05-01', '2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01'])->map(fn (string $month): string => Carbon::parse($month)->translatedFormat('F'));

        // Each series has its own colour and line style, so the two don't differ by colour alone (WCAG 1.4.1). Filament
        // paints every series in the widget's one colour unless each sets its own. Blue 500 and amber 600 from Filament's
        // palette keep 3:1 against both the light and the dark background (WCAG 1.4.11; Carbon color palettes).
        return [
            'datasets' => [
                ['label' => 'Collected', 'data' => [410000, 455000, 530000, 505000, 610000], 'borderColor' => '#3b82f6', 'backgroundColor' => '#3b82f6'],
                ['label' => 'Paid', 'data' => [440000, 470000, 480000, 460000, 520000], 'borderColor' => '#d97706', 'backgroundColor' => '#d97706', 'borderDash' => [6, 4]],
            ],
            'labels' => $months->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            // Two series need a legend, in the same place on every chart, and not clickable: Chart.js hides a series on a
            // mouse click the keyboard can't make (WCAG 2.1.1; Carbon legends and dashboards).
            'plugins' => ['legend' => ['position' => 'top', 'onClick' => null]],
            'scales' => ['y' => ['title' => ['display' => true, 'text' => 'Amount in pesos']]],
        ];
    }

    /** The values as text, read by screen readers in place of the picture (WCAG 1.1.1). */
    public function getChartAssistiveContent(): string
    {
        $data = $this->getCachedData();

        return $this->getHeading().': '.collect($data['datasets'])
            ->map(fn (array $series): string => $series['label'].' '.collect($data['labels'])
                ->map(fn (string $month, int $i): string => $month.' '.Number::currency($series['data'][$i], 'PHP'))
                ->implode(', '))
            ->implode('; ').'.';
    }
}
