<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Josephdlmd\FilamentStorybook\StorybookPlugin;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

class FixturePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('fixture')
            ->path('fixture')
            ->plugin(StorybookPlugin::make())
            ->resources([GadgetResource::class])
            ->widgets([GadgetStats::class, GadgetSalesChart::class, GadgetStockChart::class, \Josephdlmd\FilamentStorybook\Stories\Widgets\OrdersPerMonthChart::class])
            // Breaks the menu rules storybook-audit checks: an icons-only sidebar and a third level under Gadgets.
            // An ungrouped item with a child is only two levels, so it raises nothing.
            ->sidebarCollapsibleOnDesktop()
            ->navigationItems([
                NavigationItem::make('Stock report')->group('Stock Control')->parentItem('Gadgets')->url('#'),
                NavigationItem::make('Help')->icon(Heroicon::OutlinedQuestionMarkCircle)->url('#'),
                NavigationItem::make('Shortcuts')->parentItem('Help')->url('#'),
                // An action, not a place, with a count.
                NavigationItem::make('Export gadgets')->url('#')->badge('3'),
            ])
            ->darkMode(false)
            ->middleware([
                EncryptCookies::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
