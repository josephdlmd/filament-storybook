<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Josephdlmd\FilamentStorybook\Stories;

class MoneyColumnStories extends Stories
{
    public function title(): string
    {
        return 'Tables/Money column';
    }

    public function description(): string
    {
        return 'Every peso amount in a table, right-aligned so amounts line up for comparison. Basis: USWDS table 2026 (right-align numbers that can be totalled); Carbon is silent.';
    }

    public function records(): array
    {
        return [
            1 => ['amount' => 1250.5],
            2 => ['amount' => -320],
            3 => ['amount' => 1000000],
        ];
    }

    public function default(): TextColumn
    {
        return TextColumn::make('amount')
            ->money('PHP')
            ->alignEnd()
            ->sortable();
    }
}
