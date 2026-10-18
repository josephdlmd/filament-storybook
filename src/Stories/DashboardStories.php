<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Widgets\CashFlowChart;
use Josephdlmd\FilamentStorybook\Stories\Widgets\Dashboard;
use Josephdlmd\FilamentStorybook\Stories\Widgets\OrdersPerMonthChart;
use Josephdlmd\FilamentStorybook\Stories\Widgets\OrderStats;
use Josephdlmd\FilamentStorybook\Stories\Widgets\ReceivablesAgeingChart;

class DashboardStories extends Stories
{
    public function title(): string
    {
        return 'Dashboards/Key figures and charts';
    }

    public function description(): string
    {
        return 'The widgets on a dashboard, most important first: key figures at the top, then charts, then anything else, and only what helps the user decide where to look. Key figures sit two or four across, never in thirds, which Filament uses for three figures (set `getColumns()`); the most important widget comes first and widest. A key figure is a number with a short label, formatted in the app\'s locale with its unit (₱ for money); a change has an up or down arrow (`descriptionIcon()`) and says it in words, never by colour alone, and a sparkline\'s trend is also in the description. The stats in one widget are all links or none. A chart\'s type follows its purpose: a line for a trend over time, bars to compare values (the value axis starts at zero), stacked bars for parts of a whole. Its heading says what it shows, its description the main insight, with no acronyms in titles, legends or axes; one series hides its legend, and several share one legend position, not clickable; an axis says what it counts, lists every period in the range (0 for none, `null` for unknown), and gives a new year\'s first label its year; gaps in the data are shown, never joined up, with their start and end named in the text values. Each series has its own colour from Filament\'s palette, meeting 3:1 on the light and dark backgrounds, and its own line style; Filament otherwise paints every series in one colour. No gradient fills. What customers owe by days past due is one stacked bar of the ageing report\'s brackets (`ReceivablesAgeingChart`), gray while current and warmer with age; where neighbouring colours touch at under 3:1, each legend label carries its amount ("1–30 days ₱3,363.71"), so colour never carries it alone. A filter for several charts is a dashboard filter every widget reads (`HasFiltersForm` on the `Dashboard` page, read by `OrdersPerMonthChart` with `InteractsWithPageFilters`), not a dropdown on each chart; a Refresh button, not polling, brings the figures up to date. Every chart gives its values as text (`getChartAssistiveContent()`). Widgets don\'t refresh by themselves: Filament\'s stats and chart widgets reload every 5 seconds unless `$pollingInterval` is `null`, and `storybook-audit` warns. The dashboard page and widget classes below are the pattern for yours (`app/Filament/Pages`, `app/Filament/Widgets`); the stories embed them. Basis: Carbon dashboards (Sep 2026: hierarchy, "Limit the number of metrics", consistent colours, "Mirror chart modifications like filter and zoom", legends "in the same position"), Carbon chart anatomy, axes and labels, legends and simple charts (Sep 2026: titles, "start numerical axes at zero" for bar charts, "Never interpolate", "Never change axis ticks increments to accommodate data availability", no legend for one category, no acronyms, chart purposes), Carbon color palettes ("maximize contrast between neighboring colors"; "Never use a gradient"), Carbon status indicators (a change has "a \'+\' or \'-\' sign, a caret, or an arrow icon"), Carbon 2x grid ("divide by two"), Carbon tile (no mixed variants in a group); W3C WAI complex images (Apr 2026); WCAG 1.1.1, 1.4.1, 1.4.11 and 2.2.2.';
    }

    public function examples(): array
    {
        return [Dashboard::class, OrderStats::class, OrdersPerMonthChart::class, CashFlowChart::class, ReceivablesAgeingChart::class];
    }

    /** Key figures: a stats widget, each a number with a short label. */
    public function default(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(OrderStats::class),
            ]);
    }

    /** Two series over time: a line each, in its own colour and line style, with a legend that isn't clickable. */
    public function twoSeries(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(CashFlowChart::class),
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

    /** Parts of a whole: what customers owe by days past due, each bracket's amount in its legend label. */
    public function ageing(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(ReceivablesAgeingChart::class),
            ]);
    }
}
