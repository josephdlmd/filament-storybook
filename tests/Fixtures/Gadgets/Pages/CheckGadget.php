<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;

/** A custom page of one record, like a reconciliation, which the audit opens on the first record. */
class CheckGadget extends Page
{
    use InteractsWithRecord;

    protected static string $resource = GadgetResource::class;

    protected string $view = 'filament-panels::pages.page';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
