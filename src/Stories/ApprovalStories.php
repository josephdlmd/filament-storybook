<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories;

class ApprovalStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Approve and reject';
    }

    public function description(): string
    {
        return 'Deciding on a document that waits for approval, such as a purchase order. Approval is a step in the document lifecycle (`records-document-lifecycle`): Draft, then For approval, then Posted. Put both in the View page\'s `getHeaderActions()`. They show only while the document waits for approval and only to users who may decide: add `->authorize(\'approve\')` with an `approve()` method on the policy (the canvas has no policy, so the stories leave it out). Approve is the page\'s one primary button and posts the document: it takes the next number in its series and locks, so it confirms in the same danger dialog as Post, inside the same transaction, which reads the document again with `lockForUpdate()` and stops unless it is still For approval. Reject is gray, the negative action of the pair, not danger, because nothing is destroyed; it always asks for a reason and returns the document to Draft, where the person who raised it sees the reason (an inline `Callout`, `feedback-notifications.inline`) and can change it and send it again; they are also told in their notifications (the bell): info, "Order PO-000042 rejected", the reason as the body, and one View action to the order. The statuses are Draft (gray), For approval (info, blue), Posted (green) and Void (red), as the owner\'s business standards set. Each ends with a toast naming the outcome, and the status badge (`tables-status-badge`) shows the new state. Business standards from the owner: approval is a step in the lifecycle (2026-10-11); a rejection tells the person who sent it (2026-10-13). Basis: Carbon button (Sep 2026: secondary is "the negative action of the set"; danger is for "destructive effects"), Carbon modal (Sep 2026: a transactional danger modal for an irreversible action), Carbon action labels (archived: Approve "typically initiates the next step", Reject "typically blocks the process"), Carbon disabled states (hide what the user may not do), Carbon notifications pattern ("Do not leave users without next steps").';
    }

    public function records(): array
    {
        // The number Approve gives the order; the canvas has no number series to take it from.
        return [1 => ['number' => 'PO-000042', 'status' => DocumentStatus::ForApproval]];
    }

    /** Approve: the primary button, which posts the order, so it confirms in a danger dialog, then a toast naming the outcome. */
    public function default(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval)
            ->requiresConfirmation()
            ->modalHeading('Approve order?')
            ->modalDescription('It gets the next purchase order number and can no longer be changed or deleted. To correct it later, void it and issue a new one.')
            ->modalSubmitAction(fn (Action $action): Action => $action->color('danger'))
            ->modalSubmitActionLabel('Approve')
            ->successNotificationTitle(fn (?Model $record): string => "Order {$record?->number} approved")
            ->action(function (Model $record, Action $action): void {
                // On a resource, as Post does (`records-document-lifecycle`): in DB::transaction(), read the record again with
                // lockForUpdate(); unless it is still DocumentStatus::ForApproval, send a failure notification that someone else has
                // already decided on it and halt. Only then $record->update(['number' => DocumentNumber::next('PO'),
                // 'status' => DocumentStatus::Posted, 'posted_at' => now()]), so approving twice never takes a second number.
                $action->success();
            });
    }

    /** Reject: gray, asking for the reason the person who raised it will see, and back to Draft. */
    public function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->color('gray')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval)
            ->modalHeading('Reject order?')
            ->modalDescription('It goes back to draft, with your reason, for the person who raised it.')
            ->schema([
                Textarea::make('reason')
                    ->required()
                    ->rows(3),
            ])
            ->successNotificationTitle('Order rejected')
            ->action(function (Model $record, array $data, Action $action): void {
                // On a resource: $record->update(['status' => DocumentStatus::Draft, 'rejection_reason' => $data['reason']]); then tell
                // whoever sent it: Notification::make()->info()->title("Order {$record->number} rejected")->body($data['reason'])
                //     ->actions([Action::make('view')->label('View')->url(PurchaseOrderResource::getUrl('view', ['record' => $record]))])
                //     ->sendToDatabase($record->sentForApprovalBy);
                $action->success();
            });
    }
}
