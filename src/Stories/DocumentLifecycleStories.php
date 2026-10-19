<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Josephdlmd\FilamentStorybook\Documents\DocumentNumber;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories;

class DocumentLifecycleStories extends Stories
{
    public function title(): string
    {
        return 'Records/Document lifecycle';
    }

    public function description(): string
    {
        return 'A sales invoice, purchase order, receiving report or payment goes from Draft to Posted, and a posted one may be voided (`actions-void`). A document that needs approval, such as a purchase order, has Send for approval in place of Post (`sendForApproval`) and waits For approval, its figures locked, until Approve posts it or Reject returns it to Draft (`actions-approve-and-reject`). A draft can be edited and deleted. Posting gives it its number and locks it for good. A sales invoice takes the serial of the BIR pre-printed form it was written on, typed by the user and checked against the serials already used (`default`), as does a collection (`actions-record-payment`); a purchase order, receiving report or supplier payment takes the next number in the app\'s own series (`withSeries`, with `DocumentNumber` from the examples: a prefix per type, PO, RR or PV, and 6 digits that never reset, with no gaps). Once posted, the policy\'s `update()` and `delete()` return false, so Filament hides Edit and Delete and the document opens on its View page (`records-locked`, `records-view-page`). A posted document is never edited or deleted; to correct one, void it and issue a new one. Post is the View page\'s one primary button, shown only on a draft, and because it can\'t be undone it confirms in a danger dialog that says so. Work the totals out again (`forms-document-totals`) and take the number in the same database transaction as the status change. In that transaction, read the document again with `lockForUpdate()` and stop, saying someone else has already posted it, unless it is still a draft: two users posting one draft would otherwise take two numbers and leave a gap. Give the number column a unique index: it is the real guard against a serial two users enter at once, and the action catches its error and says what to check. The statuses are Draft (gray), For approval (blue), Posted (green) and Void (red), each with its word, in one status field; a payment status is a field of its own (`actions-record-payment`). Business standards from the owner (2026-10-11): one company; never edit a posted document; the form\'s serial for sales invoices and the numbering format for the rest; Void in red. Basis: Carbon modal (Sep 2026: "If the action is destructive or irreversible, then use a transactional danger modal"; the title and button reflect the action), Carbon action labels (archived: Post "adds status to a log or record"), Carbon status indicators (Aug 2026: gray for drafts, blue for workflow progress, green for normal or success), Carbon disabled states (hide what the user may not do).';
    }

    // Not imported, so the snippet's imports stay what a resource needs.
    public function model(): ?string
    {
        return Documents\SalesInvoice::class;
    }

    public function examples(): array
    {
        return [DocumentNumber::class];
    }

    public function records(): array
    {
        return [
            // Keyed by the sample invoices' ids. A draft has no number until it is posted. The canvas has no number series, so
            // this draft carries the one `withSeries` gives it.
            4 => ['id' => 4, 'number' => 'PO-000042', 'status' => DocumentStatus::Draft],
            5 => ['number' => null, 'status' => DocumentStatus::ForApproval],
            2 => ['number' => '001232', 'status' => DocumentStatus::Posted],
            1 => ['number' => '001231', 'status' => DocumentStatus::Void],
        ];
    }

    /** Post a sales invoice: it takes the serial of the BIR pre-printed form it was written on, checked against the ones already used. */
    public function default(): Action
    {
        return Action::make('post')
            ->label('Post invoice')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Draft)
            ->modalHeading('Post invoice?')
            ->modalDescription('It is recorded under the number of the form you wrote and can no longer be changed or deleted. To correct it later, void it and issue a new one.')
            ->schema([
                TextInput::make('number')
                    ->label('Invoice number')
                    ->helperText('The serial printed on the BIR form you wrote')
                    ->required()
                    ->maxLength(20)
                    ->unique()
                    ->maxWidth('xs')
                    ->validationMessages(['unique' => 'Enter the serial of the form you wrote; invoice :input is already recorded']),
            ])
            ->modalSubmitAction(fn (Action $action): Action => $action->color('danger'))
            ->modalSubmitActionLabel('Post invoice')
            ->successNotificationTitle(fn (array $data): string => "Invoice {$data['number']} posted")
            ->failureNotificationTitle(fn (array $data): string => "Invoice {$data['number']} not posted")
            ->action(function (Model $record, array $data, Action $action): void {
                try {
                    // On a resource, DB::transaction() does the same; add a posted_at date.
                    $record->getConnection()->transaction(function () use ($record, $data, $action): void {
                        // Read it again under a lock: another user may have posted it since this page loaded.
                        if ($record->newQuery()->lockForUpdate()->find($record->getKey())?->status !== DocumentStatus::Draft) {
                            $action->failureNotificationBody('Someone else has already posted it. Reload the page to see it.');
                            $action->sendFailureNotification();
                            $action->halt();
                        }

                        $record->update(['number' => $data['number'], 'status' => DocumentStatus::Posted]);
                    });
                } catch (UniqueConstraintViolationException) {
                    // The unique index on sales_invoices.number caught a serial another user posted after this form was checked.
                    $action->failureNotificationBody("Invoice {$data['number']} was just posted by someone else. Check the serial on the form you wrote.");
                    $action->sendFailureNotification();
                    $action->halt();
                }

                $action->success();
            });
    }

    /** Post a purchase order, receiving report or payment: it takes the next number in the app's own series. */
    public function withSeries(): Action
    {
        return Action::make('post')
            ->label('Post order')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Draft)
            ->requiresConfirmation()
            ->modalHeading('Post order?')
            ->modalDescription('It gets the next purchase order number and can no longer be changed or deleted. To correct it later, void it and issue a new one.')
            ->modalSubmitAction(fn (Action $action): Action => $action->color('danger'))
            ->modalSubmitActionLabel('Post order')
            ->successNotificationTitle(fn (?Model $record): string => "Order {$record?->number} posted")
            ->action(function (Model $record, Action $action): void {
                // On a resource, as in `default`: in DB::transaction(), read the record again with lockForUpdate(); unless it is still
                // DocumentStatus::Draft, send a failure notification that someone else has already posted it and halt. Only then
                // $record->update(['number' => DocumentNumber::next('PO'), 'status' => DocumentStatus::Posted, 'posted_at' => now()]),
                // so posting twice never takes a second number.
                $action->success();
            });
    }

    /** A document that needs approval: Send for approval takes the place of Post, and Approve posts it later. */
    public function sendForApproval(): Action
    {
        return Action::make('sendForApproval')
            ->label('Send for approval')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Draft)
            ->successNotificationTitle('Order sent for approval')
            ->action(function (Model $record, Action $action): void {
                // On a resource: $record->update(['status' => DocumentStatus::ForApproval, 'rejection_reason' => null]);
                $action->success();
            });
    }

    /** The status in a list: the word and its colour, both from `DocumentStatus` (`tables-status-badge`). */
    public function status(): TextColumn
    {
        return TextColumn::make('status')
            ->badge();
    }
}
