<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Imports\Supplier;

class ListTableStories extends Stories
{
    public function title(): string
    {
        return 'Tables/List table';
    }

    public function description(): string
    {
        return 'The table on every List page, pasted into the resource table\'s `configure()`. The identifier comes first; one search field says what it searches; the columns follow in order of importance, money is right-aligned and status is a badge; columns other than the identifier and name can be hidden from the column manager (`toggleable()`, one few people need starting hidden), with Reset to bring back the default, but not reordered, since Filament reorders columns only by dragging (WCAG 2.5.7). With more than one filter, the filters sit above the table, open, and apply as they change (an Apply button only when results are slow); applied filters, the search and the sort stay as the user left them for the session (`persistSearchInSession()`, `persistSortInSession()`); a row with three or more actions keeps them in its "⋯" menu, which a right-click on the row also opens (`rowActions`; fewer stay inline); and an empty list says what goes here and offers one next step (when a search finds nothing, Filament says so, and the heading can name the search), outlined because the page header already has the primary New button. Basis: Carbon data table (Sep 2026: row actions in an overflow menu, inline when fewer than three); Carbon menu (Sep 2026: menus open "through right-clicking" on "a column, or a row", "no more than 12 items"); NN/g data tables 2022 (identifier first, columns by importance and easy to hide, sortable); WCAG 2.2 2.5.7 (no drag-only reordering); USWDS table (right-align numbers); Carbon data table, filtering, search (the placeholder says what it searches; filters persist for the session) and empty states (2026: one action, "using a tertiary button for the call to action").';
    }

    /** Names the records, so the empty list's button says "New supplier", as on the resource's own page. */
    public function model(): ?string
    {
        return Supplier::class;
    }

    public function records(): array
    {
        return [
            1 => ['code' => 'SUP-0001', 'name' => 'Abra Medical Supply', 'credit_limit' => 250000, 'status' => 'Active', 'email' => 'orders@abramedical.ph', 'created_at' => '2026-08-03'],
            2 => ['code' => 'SUP-0002', 'name' => 'Benguet Trading', 'credit_limit' => null, 'status' => 'On hold', 'email' => 'sales@benguettrading.ph', 'created_at' => '2026-09-14'],
            3 => ['code' => 'SUP-0003', 'name' => 'Cebu Office Depot', 'credit_limit' => 1250000.5, 'status' => 'Active', 'email' => 'hello@cebuofficedepot.ph', 'created_at' => '2026-10-01'],
        ];
    }

    #[States('loading', 'empty', 'error', 'forbidden')]
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
                    ->placeholder('No limit')
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
                // No limit is null, as `CreditLimit` reads it; nullable() asks whether one is set.
                TernaryFilter::make('credit_limit')
                    ->label('Has a credit limit')
                    ->nullable(),
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

    /** Three or more row actions go in the row's "⋯" menu, which a right-click on the row (or Shift+F10 on it) opens too; fewer stay inline. */
    #[States('loading', 'empty', 'error', 'forbidden')]
    public function rowActions(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Create a customer to start selling to them.')
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('name'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'On hold' ? 'warning' : 'success'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('emailStatement')
                        ->label('Email statement'),
                    Action::make('downloadStatement')
                        ->label('Download statement'),
                    Action::make('putOnHold')
                        ->label('Put on hold')
                        ->visible(fn (?Model $record): bool => $record?->status === 'Active'),
                ]),
            ]);
    }
    /**
     * A date-range filter: From and To side by side in one filter, either left empty, each shown as its own indicator.
     * No presets such as "Last 7 days": no qualifying source sets them.
     */
    #[States('loading', 'empty', 'error', 'forbidden')]
    public function dateRange(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No suppliers yet')
            ->emptyStateDescription('Create a supplier to start ordering from them.')
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added on')
                    ->date()
                    ->sortable(),
            ])
            ->searchPlaceholder('Search by code or name')
            ->filters([
                Filter::make('created_at')
                    // Two dates need two of the filter grid's columns, or they are cut off.
                    ->columnSpan(2)
                    ->schema([
                        Fieldset::make('Added on')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->maxWidth('xs'),
                                DatePicker::make('until')
                                    ->label('To')
                                    ->maxWidth('xs'),
                            ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_filter([
                        'from' => filled($data['from'] ?? null) ? 'Added from '.Carbon::parse($data['from'])->isoFormat('LL') : null,
                        'until' => filled($data['until'] ?? null) ? 'Added to '.Carbon::parse($data['until'])->isoFormat('LL') : null,
                    ])),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->persistFiltersInSession();
    }
}
