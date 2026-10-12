<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A document as a PDF to print, file or send: the shared layout `filament-storybook::documents.print` on A4, tagged so
 * screen readers can read it and titled with its number. Needs spatie/laravel-pdf with its Browsershot driver
 * (spatie/browsershot, and puppeteer from npm). Copy it into the app, with a mapping like `salesInvoice()` per document.
 */
class PrintedDocument
{
    /** @param  array<string, mixed>  $document  The layout's data, as the `actions-download-pdf` stories show. */
    public static function download(array $document): StreamedResponse
    {
        return response()->streamDownload(fn () => file_put_contents('php://output', static::content($document)), static::fileName($document), ['Content-Type' => 'application/pdf']);
    }

    /**
     * The PDF itself, to attach to an email (`EmailDocument`) or keep.
     *
     * @param  array<string, mixed>  $document
     */
    public static function content(array $document): string
    {
        return Pdf::view('filament-storybook::documents.print', ['document' => $document])
            ->format('a4')
            ->tagged()
            ->meta(title: "{$document['title']} {$document['number']}")
            ->generatePdfContent();
    }

    /** @param  array<string, mixed>  $document */
    public static function fileName(array $document): string
    {
        return "{$document['title']} {$document['number']}.pdf";
    }

    /**
     * A sales invoice's printed copy. The official invoice is the BIR pre-printed form it was written on, so the copy
     * says so and carries the form's serial.
     *
     * @return array<string, mixed>
     */
    public static function salesInvoice(Model $invoice): array
    {
        return [
            'title' => 'Sales invoice (copy)',
            'number_label' => 'No.',
            'number' => $invoice->number,
            'company' => config('company'),
            'party' => [
                'label' => 'Sold to',
                'name' => data_get($invoice, 'customer.name'),
                'address' => data_get($invoice, 'customer.address'),
                'tin' => data_get($invoice, 'customer.tin'),
            ],
            // A missing value leaves its line out; Carbon would read a missing date as today. Dates read as on screen, in Filament's format.
            'details' => array_filter([
                'Date' => $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->translatedFormat(Schema::make()->getDefaultDateDisplayFormat()) : null,
                'Terms' => $invoice->terms,
                'Due date' => $invoice->due_date ? Carbon::parse($invoice->due_date)->translatedFormat(Schema::make()->getDefaultDateDisplayFormat()) : null,
            ], 'filled'),
            'columns' => [
                'description' => ['label' => 'Description'],
                'quantity' => ['label' => 'Quantity', 'format' => 'quantity'],
                'unit_price' => ['label' => 'Unit price', 'format' => 'unit price'],
                'vat' => ['label' => 'VAT'],
                'amount' => ['label' => 'Amount', 'format' => 'money'],
            ],
            'lines' => collect(data_get($invoice, 'lines'))->map(fn (mixed $line): array => [
                'description' => data_get($line, 'description'),
                'quantity' => data_get($line, 'quantity'),
                'unit_price' => data_get($line, 'unit_price'),
                'vat' => DocumentTotals::VAT_CLASSES[data_get($line, 'vat_class')] ?? null,
                'amount' => DocumentTotals::lineTotal(data_get($line, 'quantity'), data_get($line, 'unit_price')),
            ])->all(),
            // The stored totals, worked out with DocumentTotals when the invoice was saved.
            'totals' => [
                'VATable sales' => $invoice->vatable_sales,
                'VAT (12%)' => $invoice->vat,
                'Zero-rated sales' => $invoice->zero_rated_sales,
                'VAT-exempt sales' => $invoice->exempt_sales,
                'Total amount due' => $invoice->total,
            ],
            'notes' => $invoice->notes,
            'signatures' => ['Prepared by', 'Received by'],
            'statement' => 'THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX',
        ];
    }
    /** The ageing buckets of a statement and the ageing report, by days past the due date (owner's business standard). */
    public const AGEING = ['Current', '1–30 days', '31–60 days', '61–90 days', 'Over 90 days'];

    public static function ageingBucket(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue <= 0 => 'Current',
            $daysPastDue <= 30 => '1–30 days',
            $daysPastDue <= 60 => '31–60 days',
            $daysPastDue <= 90 => '61–90 days',
            default => 'Over 90 days',
        };
    }

    /**
     * A customer's statement of account: every posted invoice not yet fully paid, by its form serial, with its balance and
     * how many days it is past due, then the balance in each ageing bucket and the total due (owner's business standards).
     *
     * @param  iterable<Model>  $invoices  The customer's posted invoices with a balance due, oldest first.
     * @return array<string, mixed>
     */
    public static function statementOfAccount(Model $customer, iterable $invoices, ?Carbon $asOf = null): array
    {
        return static::openItems('Statement of account', ['label' => 'Customer', 'name' => $customer->name, 'address' => $customer->address, 'tin' => $customer->tin], 'Invoice', $invoices, 'invoice_date', $asOf);
    }

    /**
     * What we owe one supplier, to check against the statement the supplier sends; kept on file, never emailed: every
     * posted purchase order with a balance due, with how many days it is past due, then the balance in each ageing bucket
     * and the total due (owner's business standards). The supplier is a name, or a model with `name`, `address` and `tin`.
     *
     * @param  iterable<Model>  $orders  The supplier's posted orders with a balance due, oldest due first.
     * @return array<string, mixed>
     */
    public static function supplierStatement(string|Model $supplier, iterable $orders, ?Carbon $asOf = null): array
    {
        $party = is_string($supplier)
            ? ['label' => 'Supplier', 'name' => $supplier]
            : ['label' => 'Supplier', 'name' => $supplier->name, 'address' => $supplier->address, 'tin' => $supplier->tin];

        return static::openItems('Supplier statement', $party, 'Order', $orders, 'order_date', $asOf);
    }

    /**
     * A statement of open items, for a customer or a supplier: each document's date, due date, days past due and balance,
     * then the balance in each ageing bucket and the total due.
     *
     * @param  array<string, mixed>  $party
     * @param  iterable<Model>  $documents
     * @return array<string, mixed>
     */
    protected static function openItems(string $title, array $party, string $documentLabel, iterable $documents, string $dateColumn, ?Carbon $asOf): array
    {
        $asOf ??= today();
        $format = Schema::make()->getDefaultDateDisplayFormat();
        $buckets = array_fill_keys(static::AGEING, '0.00');
        $lines = [];

        foreach ($documents as $document) {
            $due = Carbon::parse($document->due_date);
            $days = max(0, (int) $due->diffInDays($asOf, false));
            $bucket = static::ageingBucket($days);
            $buckets[$bucket] = bcadd($buckets[$bucket], (string) $document->balance_due, 2);

            $lines[] = [
                'number' => $document->number,
                'date' => Carbon::parse($document->{$dateColumn})->translatedFormat($format),
                'due' => $due->translatedFormat($format),
                'days' => $days ?: null,
                'balance' => $document->balance_due,
            ];
        }

        return [
            'title' => $title,
            'number_label' => 'As of',
            'number' => $asOf->translatedFormat($format),
            'company' => config('company'),
            'party' => $party,
            'details' => [],
            'columns' => [
                'number' => ['label' => $documentLabel],
                'date' => ['label' => 'Date'],
                'due' => ['label' => 'Due date'],
                'days' => ['label' => 'Days past due', 'format' => 'quantity'],
                'balance' => ['label' => 'Balance', 'format' => 'money'],
            ],
            'lines' => $lines,
            'totals' => [...$buckets, 'Total due' => array_reduce($buckets, fn (string $sum, string $amount): string => bcadd($sum, $amount, 2), '0.00')],
        ];
    }

    /**
     * What is emailed for a collection: an acknowledgement of the payment, under the serial of the BIR receipt form it was
     * written on, which stays the official receipt (owner's business standards).
     *
     * @return array<string, mixed>
     */
    public static function paymentAcknowledgement(Model $payment): array
    {
        $settled = bcadd(bcadd((string) $payment->amount, (string) ($payment->tax_withheld ?? '0'), 2), (string) ($payment->vat_withheld ?? '0'), 2);

        return [
            'title' => 'Payment received (copy)',
            'number_label' => 'Receipt No.',
            'number' => $payment->number,
            'company' => config('company'),
            'party' => [
                'label' => 'Received from',
                'name' => data_get($payment, 'salesInvoice.customer.name'),
                'address' => data_get($payment, 'salesInvoice.customer.address'),
                'tin' => data_get($payment, 'salesInvoice.customer.tin'),
            ],
            'details' => array_filter([
                'Date received' => $payment->paid_on ? Carbon::parse($payment->paid_on)->translatedFormat(Schema::make()->getDefaultDateDisplayFormat()) : null,
                'Reference' => $payment->reference,
            ], 'filled'),
            'columns' => [
                'invoice' => ['label' => 'Invoice'],
                'amount' => ['label' => 'Amount settled', 'format' => 'money'],
            ],
            'lines' => [['invoice' => data_get($payment, 'salesInvoice.number'), 'amount' => $settled]],
            // Withholding shows only when there is some.
            'totals' => array_filter([
                'Amount received' => $payment->amount,
                'Income tax withheld' => $payment->tax_withheld,
                'VAT withheld' => $payment->vat_withheld,
            ], fn (mixed $amount): bool => (float) $amount !== 0.0) + ['Total settled' => $settled],
            'statement' => 'THIS IS NOT AN OFFICIAL RECEIPT',
        ];
    }
    /**
     * A report in the shared layout: its filters as details (the period, a customer), one line per row, and a total for
     * each money column, as on screen. Rows are models or arrays, read by the columns' keys ("customer.name").
     *
     * @param  array<string, string>  $details
     * @param  array<string, array{label: string, format?: string}>  $columns
     * @param  iterable<mixed>  $rows
     * @return array<string, mixed>
     */
    public static function report(string $title, array $details, array $columns, iterable $rows): array
    {
        $lines = collect($rows)->map(fn (mixed $row): array => collect($columns)->mapWithKeys(fn (array $column, string $key): array => [$key => data_get($row, $key)])->all());
        $money = collect($columns)->filter(fn (array $column): bool => ($column['format'] ?? null) === 'money');

        return [
            'title' => $title,
            'number_label' => 'Printed',
            'number' => today()->translatedFormat(Schema::make()->getDefaultDateDisplayFormat()),
            'company' => config('company'),
            'details' => $details,
            'columns' => $columns,
            'lines' => $lines->all(),
            'totals' => $money->mapWithKeys(fn (array $column, string $key): array => [
                $column['label'] => $lines->reduce(fn (string $sum, array $line): string => bcadd($sum, (string) ($line[$key] ?? '0'), 2), '0.00'),
            ])->all(),
        ];
    }
    /** A date-range filter's state in words, for a report's details: "Oct 1, 2026 to Oct 31, 2026", "From …", "Up to …". */
    public static function period(?string $from, ?string $until): string
    {
        $date = fn (string $date): string => Carbon::parse($date)->translatedFormat(Schema::make()->getDefaultDateDisplayFormat());

        return match (true) {
            filled($from) && filled($until) => "{$date($from)} to {$date($until)}",
            filled($from) => "From {$date($from)}",
            filled($until) => "Up to {$date($until)}",
            default => 'All dates',
        };
    }
}
