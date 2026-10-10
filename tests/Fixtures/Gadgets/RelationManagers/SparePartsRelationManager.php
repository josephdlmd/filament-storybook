<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers;

/** A second related list, so Filament shows the two as tabs. */
class SparePartsRelationManager extends PartsRelationManager
{
    protected static ?string $title = 'Spare parts';
}
