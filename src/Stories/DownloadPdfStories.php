<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPdfStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Download PDF';
    }

    public function description(): string
    {
        return 'Printing a document, or keeping it on file, as a PDF: a gray "Download PDF" button among the View page\'s header actions, shown once the document is posted (a draft isn\'t printed). Every document uses the one shared layout, `filament-storybook::documents.print`, filled from a data array (the other stories show it for a sales invoice copy, a purchase order and a payment voucher; a receiving report is like the order with received quantities). `PrintedDocument` in the examples makes the PDF with spatie/laravel-pdf: A4, tagged so screen readers can read its headings and tables, and titled with its number. The app needs spatie/laravel-pdf with its Browsershot driver (spatie/browsershot and puppeteer), and its own details in `config/company.php` (name, address, tin, vat). Sales invoices and collection receipts are written by hand on BIR pre-printed forms, which are the official copies. A sales invoice\'s printout is for the business\'s own file: titled "Sales invoice (copy)", with the form\'s serial, and not valid for claim of input tax. Customers get a statement of account instead. A collection receipt isn\'t printed; staff copy it from `records-paper-receipt`. Business standards from the owner (2026-10-11). Basis: Carbon action labels (archived: Download "transfers a file from a remote to local"); Carbon button (Sep 2026: one primary action, the rest secondary); W3C WCAG 2.2 PDF techniques (PDF6 table headers, PDF9 headings, PDF16 language, PDF18 title: a tagged PDF); WCAG 1.4.3 (4.5:1 text); USWDS table (numbers right-aligned).';
    }

    public function examples(): array
    {
        return [PrintedDocument::class];
    }

    public function records(): array
    {
        return [1 => [
            'status' => 'posted',
            'number' => '001232',
            'invoice_date' => '2026-10-11',
            'terms' => '30 days',
            'due_date' => '2026-11-10',
            'customer' => ['name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard, Cebu City 6000', 'tin' => '987-654-321-000'],
            'lines' => [
                ['description' => 'Nitrile examination gloves, medium, box of 100', 'vat_class' => 'vatable', 'quantity' => '12', 'unit_price' => '285.5'],
                ['description' => 'Insulin pens, 3 mL, pack of 5', 'vat_class' => 'exempt', 'quantity' => '2', 'unit_price' => '1450'],
            ],
            'vatable_sales' => '3058.93',
            'vat' => '367.07',
            'zero_rated_sales' => '0.00',
            'exempt_sales' => '2900.00',
            'total' => '6326.00',
            'notes' => null,
        ]];
    }

    /** The button: a posted document downloads as a PDF to print or keep. */
    public function default(): Action
    {
        return Action::make('downloadPdf')
            ->label('Download PDF')
            ->color('gray')
            ->visible(fn (?Model $record): bool => $record?->status === 'posted')
            ->action(fn (Model $record): StreamedResponse => PrintedDocument::download(PrintedDocument::salesInvoice($record)));
    }

    /** The printed copy of a sales invoice, whose official copy is the BIR form it was written on. */
    public function salesInvoiceCopy(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Sales invoice (copy)',
                'number_label' => 'No.',
                'number' => '001232',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100', 'tin' => '123-456-789-000', 'vat' => 'VAT-registered'],
                'party' => ['label' => 'Sold to', 'name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard, Cebu City 6000', 'tin' => '987-654-321-000'],
                'details' => ['Date' => 'Oct 11, 2026', 'Terms' => '30 days', 'Due date' => 'Nov 10, 2026'],
                'columns' => [
                    'description' => ['label' => 'Description'],
                    'quantity' => ['label' => 'Quantity', 'format' => 'quantity'],
                    'unit_price' => ['label' => 'Unit price', 'format' => 'unit price'],
                    'vat' => ['label' => 'VAT'],
                    'amount' => ['label' => 'Amount', 'format' => 'money'],
                ],
                'lines' => [
                    ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.50', 'vat' => '12%', 'amount' => '3426.00'],
                    ['description' => 'Insulin pens, 3 mL, pack of 5', 'quantity' => '2', 'unit_price' => '1450.00', 'vat' => 'Exempt', 'amount' => '2900.00'],
                ],
                'totals' => ['VATable sales' => '3058.93', 'VAT (12%)' => '367.07', 'Zero-rated sales' => '0.00', 'VAT-exempt sales' => '2900.00', 'Total amount due' => '6326.00'],
                'signatures' => ['Prepared by', 'Received by'],
                'statement' => 'THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX',
            ]]);
    }

    /** A purchase order: the supplier, the delivery details and the agreed prices. */
    public function purchaseOrder(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Purchase order',
                'number_label' => 'No.',
                'number' => 'PO-000042',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100', 'tin' => '123-456-789-000', 'vat' => 'VAT-registered'],
                'party' => ['label' => 'Supplier', 'name' => 'Benguet Trading', 'address' => '8 Session Road, Baguio City 2600', 'tin' => '234-567-890-000'],
                'details' => ['Date' => 'Oct 11, 2026', 'Deliver by' => 'Oct 18, 2026', 'Deliver to' => 'Main warehouse'],
                'columns' => [
                    'description' => ['label' => 'Description'],
                    'quantity' => ['label' => 'Quantity', 'format' => 'quantity'],
                    'unit_price' => ['label' => 'Unit price', 'format' => 'unit price'],
                    'amount' => ['label' => 'Amount', 'format' => 'money'],
                ],
                'lines' => [
                    ['description' => 'Isopropyl alcohol 70%, 500 mL', 'quantity' => '48', 'unit_price' => '62.50', 'amount' => '3000.00'],
                    ['description' => 'Cotton balls, 300 g', 'quantity' => '24', 'unit_price' => '95.75', 'amount' => '2298.00'],
                ],
                'totals' => ['VATable purchases' => '4730.36', 'VAT (12%)' => '567.64', 'Total' => '5298.00'],
                'signatures' => ['Prepared by', 'Approved by'],
            ]]);
    }

    /** A payment voucher: what is paid to a supplier against which orders, the tax withheld, and the signatures that approve it. */
    public function paymentVoucher(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Payment voucher',
                'number_label' => 'No.',
                'number' => 'PV-000087',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100', 'tin' => '123-456-789-000', 'vat' => 'VAT-registered'],
                'party' => ['label' => 'Paid to', 'name' => 'Benguet Trading', 'address' => '8 Session Road, Baguio City 2600', 'tin' => '234-567-890-000'],
                'details' => ['Date' => 'Oct 25, 2026', 'Paid by' => 'Check 0051177'],
                'columns' => [
                    'order' => ['label' => 'Purchase order'],
                    'invoice' => ['label' => 'Supplier\'s invoice'],
                    'amount' => ['label' => 'Amount paid', 'format' => 'money'],
                ],
                'lines' => [
                    ['order' => 'PO-000042', 'invoice' => 'BT-20931', 'amount' => '5250.70'],
                ],
                'totals' => ['Invoice total' => '5298.00', 'Tax withheld (WC158, 1%)' => '47.30', 'Amount paid' => '5250.70'],
                'signatures' => ['Prepared by', 'Approved by', 'Received by'],
            ]]);
    }
}
