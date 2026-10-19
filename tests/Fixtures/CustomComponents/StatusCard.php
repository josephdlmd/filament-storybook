<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\CustomComponents;

use Filament\Widgets\Widget;

/** A widget with a Blade view of its own: custom. */
class StatusCard extends Widget
{
    protected string $view = 'status-card';
}
