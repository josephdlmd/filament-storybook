<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class ApprovalStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Approve and reject';
    }

    public function description(): string
    {
        return 'Deciding on a record that waits for approval, such as a purchase order. Put both in the View page\'s `getHeaderActions()`. They show only while the record waits for approval and only to users who may decide: add `->authorize(\'approve\')` with an `approve()` method on the policy (the canvas has no policy, so the stories leave it out). Approve is the page\'s one primary button and acts at once; only if approving can\'t be undone in your process does it confirm, under the rule for irreversible actions. Reject is gray, the negative action of the pair, not danger, because nothing is destroyed, and asks for a reason. Each ends with a toast naming the outcome, and the status badge (`tables-status-badge`) shows the new state. Basis: Carbon button (Sep 2026: secondary is "the negative action of the set"; danger is for "destructive effects"), Carbon action labels (archived: Approve "typically initiates the next step", Reject "typically blocks the process"), Carbon disabled states (hide what the user may not do), Carbon dialogs (confirm only what can\'t be undone).';
    }

    public function records(): array
    {
        return [1 => ['number' => '1042', 'status' => 'for_approval']];
    }

    /** Approve: the primary button, one click, and a toast naming the outcome. */
    public function default(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->visible(fn (Model $record): bool => $record->status === 'for_approval')
            ->successNotificationTitle(fn (Model $record): string => "Order {$record->number} approved")
            ->action(function (Model $record, Action $action): void {
                // On a resource: $record->update(['status' => OrderStatus::Approved]);
                $action->success();
            });
    }

    /** Reject: gray, asking for the reason the person who raised it will see. */
    public function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->color('gray')
            ->visible(fn (Model $record): bool => $record->status === 'for_approval')
            ->modalHeading(fn (Model $record): string => "Reject order {$record->number}?")
            ->schema([
                Textarea::make('reason')
                    ->required()
                    ->rows(3),
            ])
            ->successNotificationTitle(fn (Model $record): string => "Order {$record->number} rejected")
            ->action(function (Model $record, array $data, Action $action): void {
                // On a resource: $record->update(['status' => OrderStatus::Rejected, 'rejection_reason' => $data['reason']]);
                $action->success();
            });
    }
}
