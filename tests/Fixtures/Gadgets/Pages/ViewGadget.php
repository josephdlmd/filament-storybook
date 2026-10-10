<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Resources\Pages\ViewRecord;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

/** No infolist, so Filament shows a disabled form; two relation managers, so they show as tabs. */
class ViewGadget extends ViewRecord
{
    protected static string $resource = GadgetResource::class;
}
