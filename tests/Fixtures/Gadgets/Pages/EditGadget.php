<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

class EditGadget extends EditRecord
{
    protected static string $resource = GadgetResource::class;

    protected function getHeaderActions(): array
    {
        // Overrides the house default, so the audit has a solid danger trigger to find.
        return [DeleteAction::make()->outlined(false)];
    }
}
