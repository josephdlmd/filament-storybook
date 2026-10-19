<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\PurchaseOrder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierStatementStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Download supplier statement';
    }

    public function description(): string
    {
        return 'What we owe one supplier, as a PDF to check against the statement the supplier sends: a gray "Download statement" button on the supplier\'s View page. It lists every posted purchase order with a balance due, oldest due first, with its date, due date, days past due and balance, then the balance in the same ageing buckets as a customer\'s statement of account and the total due, in the shared layout (`actions-download-pdf.supplierStatement`). It is kept on file, never emailed. Basis: the owner\'s business standards (2026-10-12: an internal statement to reconcile with the supplier\'s; the receivables ageing buckets); Carbon button (Sep 2026: one primary action, the rest secondary); Carbon action labels (archived: Download "transfers a file from a remote to local").';
    }

    public function examples(): array
    {
        return [PrintedDocument::class];
    }

    public function records(): array
    {
        return [1 => ['name' => 'Benguet Trading', 'address' => '8 Session Road, Baguio City 2600', 'tin' => '234-567-890-000']];
    }

    public function default(): Action
    {
        return Action::make('downloadStatement')
            ->label('Download statement')
            ->color('gray')
            ->action(fn (Model $record): StreamedResponse => PrintedDocument::download(PrintedDocument::supplierStatement(
                $record,
                // On a resource: $record->purchaseOrders()->where(...); the example orders name their supplier.
                PurchaseOrder::query()
                    ->where('supplier', $record->name)
                    ->where('status', DocumentStatus::Posted)
                    ->where('balance_due', '>', 0)
                    ->orderBy('due_date')
                    ->get(),
            )));
    }
}
