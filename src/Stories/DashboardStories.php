<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Widgets\OrdersPerMonthChart;
use Josephdlmd\FilamentStorybook\Stories\Widgets\OrderStats;

class DashboardStories extends Stories
{
    public function title(): string
    {
        return 'Dashboards/Key figures and charts';
    }

    public function description(): string
    {
        return 'The widgets on a dashboard, most important first: key figures at the top, then charts, then anything else, and only what helps the user decide where to look. A key figure is a number with a short label, formatted in the app\'s locale with its unit (₱ for money), and a description in words for any change, never colour alone. A chart\'s type follows its purpose: a line for a trend over time, bars to compare values (the value axis starts at zero), stacked bars for parts of a whole. Its heading says what it shows, its description the main insight; one series needs no legend; an axis says what it counts; gaps in the data are shown, never joined up. Each data set keeps one colour on every chart of the dashboard. Every chart gives its values as text (`getChartAssistiveContent()`). Widgets don\'t refresh by themselves: Filament\'s stats and chart widgets reload every 5 seconds unless `$pollingInterval` is `null`, and `storybook-audit` warns. The two widget classes below are the pattern for yours (`app/Filament/Widgets`); the stories embed them. Basis: Carbon dashboards (Sep 2026: hierarchy, "Limit the number of metrics", consistent colours), Carbon chart anatomy, axes and labels, legends and simple charts (Sep 2026: titles, "start numerical axes at zero" for bar charts, "Never interpolate", no legend for one category, chart purposes); W3C WAI complex images (Apr 2026); WCAG 1.1.1, 1.4.1, 1.4.11 and 2.2.2.';
    }

    public function examples(): array
    {
        return [OrderStats::class, OrdersPerMonthChart::class];
    }

    /** Key figures: a stats widget, each a number with a short label. */
    public function default(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(OrderStats::class),
            ]);
    }

    /** A chart: a bar chart that compares months, with its values as text. */
    public function chart(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(OrdersPerMonthChart::class),
            ]);
    }
}
