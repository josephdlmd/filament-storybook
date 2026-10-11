<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class VoidStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Void';
    }

    public function description(): string
    {
        return 'Cancelling a posted document (`records-document-lifecycle`), such as an invoice issued in error. It is never deleted: it keeps its number, stays in the list marked Void (red), and the reason, who voided it and when are kept on it. Put it in the View page\'s `getHeaderActions()`. It shows only on a posted document and only to users who may void: add `->authorize(\'void\')` with a `void()` policy method that checks a permission given to a manager or accountant role, not to everyone who may post (the canvas has no policy, so the story leaves it out). Among the page\'s other actions the button is low-emphasis danger (outlined); its dialog repeats the label, says the document keeps its number and that this can\'t be undone, asks for the reason, and confirms with a solid danger button. A document with payments that aren\'t void can\'t be voided until they are: the dialog says so and offers nothing to confirm. Ask the database each time, with a model method such as `hasLivePayments()` (`$this->payments()->where(\'status\', \'!=\', \'void\')->exists()`); never a count loaded with the page, which is gone after the page\'s first action. Payments are voided the same way, which gives their amount back to the balance due. The word is Void, not Cancel, because Cancel is the dialog\'s own button for closing it. Business standards from the owner (2026-10-11). Basis: Carbon modal (Sep 2026: a danger modal for destructive or irreversible actions; the title and button reflect the action), Carbon button (Sep 2026: danger for destructive effects; low emphasis among other actions), Carbon common actions (Aug 2026: Cancel "stops the current action and closes the component"), Carbon disabled states (hide what the user may not do).';
    }

    // Not imported, so the snippet's imports stay what a resource needs.
    public function model(): ?string
    {
        return Documents\SalesInvoice::class;
    }

    public function records(): array
    {
        return [1 => ['id' => 3, 'number' => '001234', 'status' => 'posted']];
    }

    /** Void a posted invoice, giving the reason; while it has payments that aren't void, the dialog says to void those first. */
    public function default(): Action
    {
        return Action::make('void')
            ->label('Void invoice')
            ->color('danger')
            ->outlined()
            ->visible(fn (?Model $record): bool => $record?->status === 'posted')
            ->modalHeading(fn (?Model $record): string => $record?->hasLivePayments() ? "Invoice {$record->number} has payments" : "Void invoice {$record?->number}?")
            ->modalDescription(fn (?Model $record): string => $record?->hasLivePayments()
                ? 'Void its payments first, from its list of payments. Then you can void the invoice.'
                : 'It keeps its number and stays in the list, marked Void. This cannot be undone.')
            ->schema(fn (?Model $record): array => $record?->hasLivePayments() ? [] : [
                Textarea::make('reason')
                    ->required()
                    ->rows(3),
            ])
            ->modalSubmitAction(fn (Action $action, ?Model $record): Action|false => $record?->hasLivePayments() ? false : $action->outlined(false))
            ->modalCancelAction(fn (?Model $record): ?bool => $record?->hasLivePayments() ? false : null)
            ->modalSubmitActionLabel('Void invoice')
            ->successNotificationTitle(fn (?Model $record): string => "Invoice {$record?->number} voided")
            ->action(function (Model $record, array $data, Action $action): void {
                // Checked again here, since a request can call the action without its button.
                if ($record->hasLivePayments()) {
                    $action->halt();
                }

                // On a resource: $record->update(['status' => InvoiceStatus::Void, 'void_reason' => $data['reason'], 'voided_by' => auth()->id(), 'voided_at' => now()]);
                $action->success();
            });
    }
}
