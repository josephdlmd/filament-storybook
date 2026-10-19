<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\CustomComponents\Filament;

use Illuminate\Contracts\View\View;

/** A trait that swaps a page's header for a view of its own: custom. */
trait HasOwnHeader
{
    public function getHeader(): ?View
    {
        return view('list-header');
    }
}
