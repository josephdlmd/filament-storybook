<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
        return 'The table on every List page, pasted into the resource table\'s `configure()`. The identifier comes first; one search field says what it searches; the columns follow in order of importance, money is right-aligned and status is a badge; columns other than the identifier and name can be hidden from the column manager (`toggleable()`, one few people need starting hidden), with Reset to bring back the default, but not reordered, since Filament reorders columns only by dragging (WCAG 2.5.7). With more than one filter, the filters sit above the table, open, and apply as they change (an Apply button only when results are slow); applied filters, the search and the sort stay as the user left them for the session (`persistSearchInSession()`, `persistSortInSession()`); and an empty list says what goes here and offers one next step (when a search finds nothing, Filament says so, and the heading can name the search), outlined because the page header already has the primary New button. Basis: NN/g data tables 2022 (identifier first, columns by importance and easy to hide, sortable); WCAG 2.2 2.5.7 (no drag-only reordering); USWDS table (right-align numbers); Carbon data table, filtering, search (the placeholder says what it searches; filters persist for the session) and empty states (2026: one action, "using a tertiary button for the call to action").';
    }

    public function records(): array
    {
        return [
            1 => ['code' => 'SUP-0001', 'name' => 'Abra Medical Supply', 'credit_limit' => 250000, 'status' => 'Active', 'email' => 'orders@abramedical.ph'],
            2 => ['code' => 'SUP-0002', 'name' => 'Benguet Trading', 'credit_limit' => 0, 'status' => 'On hold', 'email' => 'sales@benguettrading.ph'],
            3 => ['code' => 'SUP-0003', 'name' => 'Cebu Office Depot', 'credit_limit' => 1250000.5, 'status' => 'Active', 'email' => 'hello@cebuofficedepot.ph'],
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
                // The identifier and name always show; the others can be hidden from the column manager, and Reset brings them back.
                TextColumn::make('credit_limit')
                    ->money('PHP')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->toggleable()
                    ->badge()
                    // On hold needs attention, so it is warning (`tables-status-badge`); in an app, put the colour on the status enum.
                    ->color(fn (string $state): string => $state === 'On hold' ? 'warning' : 'success'),
                // A column few people need starts hidden.
                TextColumn::make('email')
                    ->label('Email address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->searchPlaceholder('Search by code or name')
            ->filters([
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'On hold' => 'On hold']),
                TernaryFilter::make('has_credit_limit')
                    ->label('Has a credit limit'),
            ], layout: FiltersLayout::AboveContent)
            // Filters apply as they change; add an Apply button (deferFilters()) only when the results are slow (Carbon filtering).
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->emptyStateHeading('No suppliers yet')
            ->emptyStateDescription('Create a supplier to start ordering from them.')
            ->emptyStateActions([
                // The page header already has the primary New button, so this one is outlined (Carbon empty states: tertiary).
                CreateAction::make()
                    ->outlined(),
            ]);
    }
}
