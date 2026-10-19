<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\CustomComponents\Filament;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;

/** Extends nothing, but draws a Blade view of its own: custom. */
class AgeingBar
{
    public static function make(): ViewContract
    {
        return View::make('ageing-bar');
    }
}
