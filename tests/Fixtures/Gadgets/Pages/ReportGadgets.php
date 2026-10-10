<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Resources\Pages\Page;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

/** A page that needs a route parameter the audit can't supply. */
class ReportGadgets extends Page
{
    protected static string $resource = GadgetResource::class;

    public int $year;

    public function mount(int $year): void
    {
        $this->year = $year;
    }
}
