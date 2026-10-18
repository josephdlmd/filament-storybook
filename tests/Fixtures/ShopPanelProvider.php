<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;

/** A second panel without the plugin, like an app's warehouse panel, audited with the "panel" option. */
class ShopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('shop')->path('shop');
    }
}
