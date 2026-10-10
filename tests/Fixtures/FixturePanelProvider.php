<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
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
            ->widgets([GadgetStats::class, \Josephdlmd\FilamentStorybook\Stories\Widgets\OrdersPerMonthChart::class])
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
