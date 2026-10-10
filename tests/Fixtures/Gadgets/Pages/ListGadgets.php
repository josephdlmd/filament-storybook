<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Resources\Pages\ListRecords;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

class ListGadgets extends ListRecords
{
    protected static string $resource = GadgetResource::class;
}
