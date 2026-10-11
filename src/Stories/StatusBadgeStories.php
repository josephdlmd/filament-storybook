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
        return 'A record\'s state in a table: gray for a draft or not started, info (blue) for in progress, such as waiting for approval, warning (yellow) for something that needs attention, success for done, danger for an error or something critical, always with the word. A document has one status field (Draft, For approval, Posted, Void; `records-document-lifecycle`), and a payment status is a field of its own (`actions-record-payment`), so a list never mixes the two in one column. In an app, put the label and colour on the status enum (`HasLabel`, `HasColor`) and use just `->badge()`. Basis: Carbon status indicator pattern (colour meanings, colour never alone) and WCAG 2.2 1.4.1 Use of Color.';
    }

    public function records(): array
    {
        return [
            1 => ['status' => 'draft'],
            2 => ['status' => 'for_approval'],
            3 => ['status' => 'posted'],
            4 => ['status' => 'void'],
        ];
    }

    public function default(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->ucfirst())
            ->color(fn (string $state): string => match ($state) {
                'for_approval' => 'info',
                'posted' => 'success',
                'void' => 'danger',
                default => 'gray',
            });
    }
}
