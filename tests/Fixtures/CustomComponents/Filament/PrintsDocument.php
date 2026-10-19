<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\CustomComponents\Filament;

use Illuminate\Contracts\View\View;

/** Draws the package's shared print layout, a package view: not custom. */
class PrintsDocument
{
    public function render(): View
    {
        return view('filament-storybook::documents.print');
    }
}
