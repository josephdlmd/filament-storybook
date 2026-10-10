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
        return 'A record\'s state in a table: gray for a draft or not started, info (blue) for in progress, such as waiting for approval, warning (yellow) for something that needs attention, success for done, danger for an error or something critical, always with the word. In an app, put the label and colour on the status enum (`HasLabel`, `HasColor`) and use just `->badge()`. Basis: Carbon status indicator pattern (colour meanings, colour never alone) and WCAG 2.2 1.4.1 Use of Color.';
    }

    public function records(): array
    {
        return [
            1 => ['status' => 'draft'],
            2 => ['status' => 'for_approval'],
            3 => ['status' => 'approved'],
            4 => ['status' => 'rejected'],
            5 => ['status' => 'overdue'],
        ];
    }

    public function default(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->ucfirst())
            ->color(fn (string $state): string => match ($state) {
                'approved' => 'success',
                'for_approval' => 'info',
                'rejected', 'overdue' => 'danger',
                default => 'gray',
            });
    }
}
