<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\CustomComponents;

use Illuminate\View\Component;

/** A Blade view component: custom. */
class Pill extends Component
{
    public function render(): string
    {
        return '<span>{{ $slot }}</span>';
    }
}
