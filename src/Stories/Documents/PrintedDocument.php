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
        $name = "{$document['title']} {$document['number']}";

        $pdf = Pdf::view('filament-storybook::documents.print', ['document' => $document])
            ->format('a4')
            ->tagged()
            ->meta(title: $name);

        return response()->streamDownload(fn () => file_put_contents('php://output', $pdf->generatePdfContent()), "{$name}.pdf", ['Content-Type' => 'application/pdf']);
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
}
