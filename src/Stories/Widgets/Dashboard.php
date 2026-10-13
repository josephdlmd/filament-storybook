<?php

namespace Josephdlmd\FilamentStorybook\Stories\Widgets;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The dashboard page with one filter every widget reads (Carbon dashboards: "Mirror chart modifications like filter and
 * zoom"), not a dropdown on each chart. A widget reads it with `InteractsWithPageFilters` (see `OrdersPerMonthChart`).
 * Copy it to `app/Filament/Pages/Dashboard.php`; the panel's `->pages([Dashboard::class])` uses it in place of Filament's.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                // A date range is one filter with From and To side by side, either left empty (Carbon date picker).
                Section::make()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('from')
                            ->maxWidth('xs'),
                        DatePicker::make('until')
                            ->label('To')
                            ->afterOrEqual('from')
                            ->maxWidth('xs'),
                    ]),
            ]);
    }
}
