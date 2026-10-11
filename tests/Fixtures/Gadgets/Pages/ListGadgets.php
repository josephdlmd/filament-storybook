<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

class ListGadgets extends ListRecords
{
    protected static string $resource = GadgetResource::class;

    /** Four buttons, one of them icon-only without a tooltip. */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import')->color('gray'),
            Action::make('export')->color('gray'),
            Action::make('print')->label('Print')->icon('heroicon-o-printer')->iconButton()->color('gray'),
        ];
    }
}
