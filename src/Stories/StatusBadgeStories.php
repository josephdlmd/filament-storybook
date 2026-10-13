<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Tables\Columns\TextColumn;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentStatus;

class StatusBadgeStories extends Stories
{
    public function title(): string
    {
        return 'Tables/Status badge';
    }

    public function description(): string
    {
        return 'A record\'s state in a table: gray for a draft or not started, info (blue) for in progress, such as waiting for approval, warning (yellow) for something that needs attention, success for done, danger for an error or something critical, always with the word. A document has one status field (Draft, For approval, Posted, Void; `records-document-lifecycle`), and a payment status is a field of its own (`actions-record-payment`), so a list never mixes the two in one column. The word and colour are on the status enum (`DocumentStatus`, `HasLabel` and `HasColor`; copy it from the examples and cast the model\'s `status` to it), so the column is just `->badge()`, and a select or filter takes `->options(DocumentStatus::class)`. Basis: Carbon status indicator pattern (colour meanings, colour never alone) and WCAG 2.2 1.4.1 Use of Color.';
    }

    public function examples(): array
    {
        return [DocumentStatus::class];
    }

    public function records(): array
    {
        return [
            1 => ['status' => DocumentStatus::Draft],
            2 => ['status' => DocumentStatus::ForApproval],
            3 => ['status' => DocumentStatus::Posted],
            4 => ['status' => DocumentStatus::Void],
        ];
    }

    public function default(): TextColumn
    {
        return TextColumn::make('status')
            ->badge();
    }
}
