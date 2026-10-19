<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Documents\PrintedDocument;
use Josephdlmd\FilamentStorybook\Stories;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPdfStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Download PDF';
    }

    public function description(): string
    {
        return 'Printing a document, or keeping it on file, as a PDF: a gray "Download PDF" button among the View page\'s header actions, shown once the document is posted (a draft isn\'t printed). Every document uses the one shared layout, `filament-storybook::documents.print`, filled from a data array (the other stories show it for a sales invoice copy, a purchase order and a payment voucher; a receiving report is like the order with received quantities). `PrintedDocument` in the examples makes the PDF with spatie/laravel-pdf: A4, tagged so screen readers can read its headings and tables, and titled with its number. The app needs spatie/laravel-pdf with its Browsershot driver (spatie/browsershot and puppeteer), and its own details in `config/company.php` (name, address, and the email address documents are sent from). A sales invoice written on a pre-printed form prints as "Sales invoice (copy)", with the form\'s serial; it is kept on file and may be emailed to the customer (`actions-email-document`). What a customer gets by email for a collection is a payment acknowledgement, "Payment received (copy)" (`paymentAcknowledgement`). An app that keeps Philippine books prints TINs, VAT, the tax withheld and BIR\'s wording with filament-storybook-ph (`actions-download-pdf-with-tax-details`). A statement of account lists a customer\'s open invoices with their ageing (`statementOfAccount`); a supplier statement lists what is owed to one supplier the same way, kept on file to check against the statement the supplier sends, never emailed (`supplierStatement`, `actions-download-supplier-statement`). Business standards from the owner (2026-10-11 and 2026-10-12). Basis: Carbon action labels (archived: Download "transfers a file from a remote to local"); Carbon button (Sep 2026: one primary action, the rest secondary); W3C WCAG 2.2 PDF techniques (PDF6 table headers, PDF9 headings, PDF16 language, PDF18 title: a tagged PDF); WCAG 1.4.3 (4.5:1 text); USWDS table (numbers right-aligned).';
    }

    public function examples(): array
    {
        return [PrintedDocument::class];
    }

    public function records(): array
    {
        return [1 => [
            'status' => DocumentStatus::Posted,
            'number' => '001232',
            'invoice_date' => '2026-10-11',
            'terms' => '30 days',
            'due_date' => '2026-11-10',
            'customer' => ['name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard, Cebu City 6000'],
            'lines' => [
                ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.5'],
                ['description' => 'Insulin pens, 3 mL, pack of 5', 'quantity' => '2', 'unit_price' => '1450'],
            ],
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
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted)
            ->action(fn (Model $record): StreamedResponse => PrintedDocument::download(PrintedDocument::salesInvoice($record)));
    }

    /** The printed copy of a sales invoice, under the serial of the form it was written on. */
    public function salesInvoiceCopy(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Sales invoice (copy)',
                'number_label' => 'No.',
                'number' => '001232',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100'],
                'party' => ['label' => 'Sold to', 'name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard, Cebu City 6000'],
                'details' => ['Date' => 'Oct 11, 2026', 'Terms' => '30 days', 'Due date' => 'Nov 10, 2026'],
                'columns' => [
                    'description' => ['label' => 'Description'],
                    'quantity' => ['label' => 'Quantity', 'format' => 'quantity'],
                    'unit_price' => ['label' => 'Unit price', 'format' => 'unit price'],
                    'amount' => ['label' => 'Amount', 'format' => 'money'],
                ],
                'lines' => [
                    ['description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '12', 'unit_price' => '285.50', 'amount' => '3426.00'],
                    ['description' => 'Insulin pens, 3 mL, pack of 5', 'quantity' => '2', 'unit_price' => '1450.00', 'amount' => '2900.00'],
                ],
                'totals' => ['Total amount due' => '6326.00'],
                'signatures' => ['Prepared by', 'Received by'],
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
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100'],
                'party' => ['label' => 'Supplier', 'name' => 'Benguet Trading', 'address' => '8 Session Road, Baguio City 2600'],
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
                'totals' => ['Total' => '5298.00'],
                'signatures' => ['Prepared by', 'Approved by'],
            ]]);
    }

    /** A payment voucher: what is paid to a supplier against which orders, and the signatures that approve it. */
    public function paymentVoucher(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Payment voucher',
                'number_label' => 'No.',
                'number' => 'PV-000087',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100'],
                'party' => ['label' => 'Paid to', 'name' => 'Benguet Trading', 'address' => '8 Session Road, Baguio City 2600'],
                'details' => ['Date' => 'Oct 25, 2026', 'Paid by' => 'Check 0051177'],
                'columns' => [
                    'order' => ['label' => 'Purchase order'],
                    'invoice' => ['label' => 'Supplier\'s invoice'],
                    'amount' => ['label' => 'Amount paid', 'format' => 'money'],
                ],
                'lines' => [
                    ['order' => 'PO-000042', 'invoice' => 'BT-20931', 'amount' => '5298.00'],
                ],
                'totals' => ['Amount paid' => '5298.00'],
                'signatures' => ['Prepared by', 'Approved by', 'Received by'],
            ]]);
    }
    /** What a customer gets by email for a collection: an acknowledgement under the receipt form's serial. */
    public function paymentAcknowledgement(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Payment received (copy)',
                'number_label' => 'Receipt No.',
                'number' => '000387',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100'],
                'party' => ['label' => 'Received from', 'name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard, Cebu City 6000'],
                'details' => ['Date received' => 'Nov 8, 2026', 'Reference' => 'Check 0047731'],
                'columns' => [
                    'invoice' => ['label' => 'Invoice'],
                    'amount' => ['label' => 'Amount settled', 'format' => 'money'],
                ],
                'lines' => [
                    ['invoice' => '001232', 'amount' => '6326.00'],
                ],
                'totals' => ['Amount received' => '6326.00'],
            ]]);
    }

    /** A statement of account: the customer's open invoices, how far past due each is, and the balance in each ageing bucket. */
    public function statementOfAccount(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Statement of account',
                'number_label' => 'As of',
                'number' => 'Nov 30, 2026',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100'],
                'party' => ['label' => 'Customer', 'name' => 'Cebu Diagnostics Laboratory', 'address' => '45 Osmeña Boulevard, Cebu City 6000'],
                'details' => [],
                'columns' => [
                    'invoice' => ['label' => 'Invoice'],
                    'date' => ['label' => 'Date'],
                    'due' => ['label' => 'Due date'],
                    'days' => ['label' => 'Days past due', 'format' => 'quantity'],
                    'balance' => ['label' => 'Balance', 'format' => 'money'],
                ],
                'lines' => [
                    ['invoice' => '001214', 'date' => 'Aug 20, 2026', 'due' => 'Sep 19, 2026', 'days' => '72', 'balance' => '890.50'],
                    ['invoice' => '001233', 'date' => 'Oct 14, 2026', 'due' => 'Nov 13, 2026', 'days' => '17', 'balance' => '2363.71'],
                    ['invoice' => '001240', 'date' => 'Nov 20, 2026', 'due' => 'Dec 20, 2026', 'days' => null, 'balance' => '4480.00'],
                ],
                'totals' => ['Current' => '4480.00', '1–30 days' => '2363.71', '31–60 days' => '0.00', '61–90 days' => '890.50', 'Over 90 days' => '0.00', 'Total due' => '7734.21'],
            ]]);
    }

    /** A supplier statement: what we owe one supplier, to check against the statement they send; kept on file, not emailed. */
    public function supplierStatement(): View
    {
        return View::make('filament-storybook::documents.document')
            ->viewData(['document' => [
                'title' => 'Supplier statement',
                'number_label' => 'As of',
                'number' => 'Oct 12, 2026',
                'company' => ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100'],
                'party' => ['label' => 'Supplier', 'name' => 'Luzon Medical Gases', 'address' => '3 MacArthur Highway, San Fernando, Pampanga 2000'],
                'details' => [],
                'columns' => [
                    'number' => ['label' => 'Order'],
                    'date' => ['label' => 'Date'],
                    'due' => ['label' => 'Due date'],
                    'days' => ['label' => 'Days past due', 'format' => 'quantity'],
                    'balance' => ['label' => 'Balance', 'format' => 'money'],
                ],
                'lines' => [
                    ['number' => 'PO-000043', 'date' => 'Aug 15, 2026', 'due' => 'Sep 14, 2026', 'days' => '28', 'balance' => '7000.00'],
                    ['number' => 'PO-000046', 'date' => 'Oct 1, 2026', 'due' => 'Oct 31, 2026', 'days' => null, 'balance' => '3360.00'],
                ],
                'totals' => ['Current' => '3360.00', '1–30 days' => '7000.00', '31–60 days' => '0.00', '61–90 days' => '0.00', 'Over 90 days' => '0.00', 'Total due' => '10360.00'],
            ]]);
    }
}
