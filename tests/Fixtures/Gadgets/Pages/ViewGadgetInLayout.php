<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\Pages;

use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadget;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\GadgetResource;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers\PartsRelationManager;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\Gadgets\RelationManagers\SparePartsRelationManager;

/** The same record built as `records-view-page` says: related lists stacked in the main column, Edit on the summary. */
class ViewGadgetInLayout extends ViewRecord
{
    protected static string $resource = GadgetResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['@4xl' => 4])
                    ->gridContainer()
                    ->schema([
                        Group::make([
                            Livewire::make(PartsRelationManager::class, fn (Gadget $record): array => ['ownerRecord' => $record, 'pageClass' => static::class])
                                ->key('parts')
                                ->visible(fn (Gadget $record): bool => PartsRelationManager::canViewForRecord($record, static::class)),
                            Livewire::make(SparePartsRelationManager::class, fn (Gadget $record): array => ['ownerRecord' => $record, 'pageClass' => static::class])
                                ->key('spare-parts')
                                ->visible(fn (Gadget $record): bool => SparePartsRelationManager::canViewForRecord($record, static::class)),
                        ])
                            ->columnSpan(['@4xl' => 3]),
                        Section::make('Details')
                            ->afterHeader([
                                EditAction::make()->color('gray'),
                            ])
                            ->schema([
                                TextEntry::make('code'),
                                TextEntry::make('price')->money('PHP'),
                            ])
                            ->columnSpan(['@4xl' => 1]),
                    ]),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
