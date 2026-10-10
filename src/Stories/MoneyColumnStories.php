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
        return 'Every peso amount in a table: one formatter, right-aligned, decimals kept, negatives shown by sign and never by colour. Basis: right-aligned numbers (GOV.UK table, Microsoft style guide). One formatter, kept decimals and uncoloured negatives are house choices.';
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
