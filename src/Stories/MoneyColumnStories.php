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
        return 'Every peso amount in a table, right-aligned so amounts line up for comparison. Basis: GOV.UK table (right-align numbers being compared) and USWDS table (numeric cells right-aligned).';
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
