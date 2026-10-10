<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Josephdlmd\FilamentStorybook\Stories;

class StatusBadgeStories extends Stories
{
    public function title(): string
    {
        return 'Tables/Status badge';
    }

    public function description(): string
    {
        return 'A record\'s state in a table. One colour per meaning (success done, warning to-do, danger only for losses and failures, gray neutral), always with the word (colour alone fails WCAG 1.4.1). In an app, put the label and colour on the status enum (`HasLabel`, `HasColor`) and use just `->badge()`.';
    }

    public function records(): array
    {
        return [
            1 => ['status' => 'draft'],
            2 => ['status' => 'for_approval'],
            3 => ['status' => 'approved'],
            4 => ['status' => 'cancelled'],
        ];
    }

    public function default(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(fn (string $state): string => str($state)->headline())
            ->color(fn (string $state): string => match ($state) {
                'approved' => 'success',
                'for_approval' => 'warning',
                'cancelled' => 'danger',
                default => 'gray',
            });
    }
}
