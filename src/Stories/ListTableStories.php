<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Josephdlmd\FilamentStorybook\Stories;

class ListTableStories extends Stories
{
    public function title(): string
    {
        return 'Tables/List table';
    }

    public function description(): string
    {
        return 'The table on every List page, pasted into the resource table\'s `configure()`. The identifier comes first and is searchable, at most 8 columns are visible, money is right-aligned, status is a badge, and 25 rows show per page. Clicking a row opens the record, with at most one inline row action.';
    }

    public function records(): array
    {
        return [
            1 => ['code' => 'SUP-0001', 'name' => 'Abra Medical Supply', 'credit_limit' => 250000, 'status' => 'Active'],
            2 => ['code' => 'SUP-0002', 'name' => 'Benguet Trading', 'credit_limit' => 0, 'status' => 'On hold'],
            3 => ['code' => 'SUP-0003', 'name' => 'Cebu Office Depot', 'credit_limit' => 1250000.5, 'status' => 'Active'],
        ];
    }

    public function default(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->money('PHP')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'On hold' => 'On hold']),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25);
    }
}
