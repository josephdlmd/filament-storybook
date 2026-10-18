<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;

/** An app story on the app's real model, whose sample row has a real row's id. */
class GadgetStories extends Stories
{
    public function title(): string
    {
        return 'Fixtures/Gadgets';
    }

    public function model(): ?string
    {
        return Gadget::class;
    }

    public function records(): array
    {
        return [1 => ['id' => 1, 'code' => 'G-1', 'name' => 'Sample gadget']];
    }

    /** A list with a delete on each row. */
    public function default(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')])->recordActions([DeleteAction::make()]);
    }
}
