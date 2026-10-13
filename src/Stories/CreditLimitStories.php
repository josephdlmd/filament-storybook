<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\CreditLimit;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories\Documents\SalesOrder;

class CreditLimitStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Credit limit';
    }

    public function description(): string
    {
        return 'A sales order that would take its customer over their credit limit is held. On its View page a warning callout above the buttons says by how much (`default`), and the primary button is "Send for approval" in place of "Post order" (`sendForApproval`); the order goes For approval (info), saving the reason, which its View page shows beside the status (`approvalReason`), and someone who may approve orders posts it or sends it back (`actions-approve-and-reject`). An order within the limit is posted as any other (`records-document-lifecycle.withSeries`), and posting checks the limit again inside its transaction, with the customer locked: if another order has since used the room, it isn\'t posted, and the toast says to send it for approval. What counts is `CreditLimit::exposure()`: unpaid balances of posted invoices, posted orders not yet invoiced, and this order; reaching the limit exactly fits, and a customer with no limit set is never held. Basis: the owner\'s business standards (2026-10-12: hold for approval; what counts toward the limit); Carbon notification (Sep 2026: a warning callout "above the relevant button or input", there when the page loads); the lifecycle\'s approval step (`records-document-lifecycle.sendForApproval`).';
    }

    public function model(): ?string
    {
        return SalesOrder::class;
    }

    public function examples(): array
    {
        return [CreditLimit::class];
    }

    public function records(): array
    {
        // Abra's limit is ₱250,000.00 and ₱205,890.50 is taken, so this ₱56,509.50 order is ₱12,400.00 over. The reason is what
        // sending it for approval saves.
        return [1 => ['id' => 3, 'number' => null, 'status' => DocumentStatus::Draft, 'customer_id' => 1, 'total' => '56509.50', 'approval_reason' => "₱12,400.00 over Abra Medical Supply's ₱250,000.00 credit limit"]];
    }

    /** Above the buttons of a draft order over the limit, from when the page loads. */
    public function default(): Callout
    {
        return Callout::make('Over the credit limit')
            ->description(fn (?Model $record): ?string => $record?->customer && ($over = CreditLimit::over($record->customer, (string) $record->total))
                ? "This order would take {$record->customer->name} ".Number::currency((float) $over, 'PHP').' over its '.Number::currency((float) $record->customer->credit_limit, 'PHP').' credit limit, so it needs approval before it is posted.'
                : null)
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Draft && $record->customer && CreditLimit::over($record->customer, (string) $record->total) !== null)
            ->warning();
    }

    /** The primary button of a draft order over the limit, in place of "Post order". */
    public function sendForApproval(): Action
    {
        return Action::make('sendForApproval')
            ->label('Send for approval')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Draft && $record->customer && CreditLimit::over($record->customer, (string) $record->total) !== null)
            ->successNotificationTitle('Order sent for approval')
            ->action(function (Model $record, Action $action): void {
                // On a resource: $record->update(['status' => DocumentStatus::ForApproval,
                // 'approval_reason' => CreditLimit::reason($record->customer, CreditLimit::over($record->customer, (string) $record->total))]);
                $action->success();
            });
    }

    /** On a held order's View page, beside its For approval status: the reason saved when it was sent. */
    public function approvalReason(): TextEntry
    {
        // On a resource: ->visible(fn (?SalesOrder $record): bool => $record?->status === DocumentStatus::ForApproval).
        return TextEntry::make('approval_reason')
            ->label('Needs approval because');
    }
}
