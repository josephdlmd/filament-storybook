{{--
    A printed document: the shared layout for sales invoice copies, purchase orders, receiving reports and payments.
    `$document` holds: title, number_label, number, company (name, address, tin, vat), party (label, name, address, tin; none on a report),
    details (label => value), columns (key => [label, format: text|date|quantity|money|unit price]), lines (rows keyed like columns),
    totals (label => amount; the last is the total), notes, signatures (labels) and statement (BIR's wording, if any).
--}}
@php
    $format = fn (mixed $value, string $as = 'text'): string => match (true) {
        blank($value) => '',
        $as === 'money' => \Illuminate\Support\Number::currency((float) $value, 'PHP'),
        // A unit price keeps the decimals it was stored with (2 to 4), so quantity × price still gives the line's amount.
        $as === 'unit price' => \Illuminate\Support\Number::currency((float) $value, 'PHP', precision: max(2, strlen(rtrim(strstr((string) $value, '.') ?: '.', '0')) - 1)),
        $as === 'quantity' => \Illuminate\Support\Number::format((float) $value, maxPrecision: 3),
        // A date reads as it does on screen, in Filament's format.
        $as === 'date' => \Illuminate\Support\Carbon::parse($value)->translatedFormat(\Filament\Schemas\Schema::make()->getDefaultDateDisplayFormat()),
        default => (string) $value,
    };
@endphp

<style>
    .fs-document { box-sizing: border-box; max-width: 210mm; margin: 0 auto; padding: 12mm; background: #fff; color: #111; font: 10pt/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    .fs-document * { box-sizing: border-box; }
    .fs-document h1, .fs-document h2, .fs-document p, .fs-document dl, .fs-document dd { margin: 0; }
    .fs-document__header { display: flex; justify-content: space-between; gap: 8mm; padding-bottom: 4mm; border-bottom: 1px solid #111; }
    .fs-document__company { font-size: 12pt; font-weight: 600; }
    .fs-document__title { text-align: end; }
    .fs-document__title h1 { font-size: 15pt; font-weight: 600; }
    .fs-document__parties { display: flex; justify-content: space-between; gap: 8mm; margin: 5mm 0; }
    .fs-document__parties h2 { font-size: 10pt; font-weight: 600; }
    .fs-document dl { display: grid; grid-template-columns: auto auto; gap: 0 4mm; align-content: start; }
    .fs-document dt { color: #444; }
    .fs-document table { width: 100%; border-collapse: collapse; }
    .fs-document__lines { overflow-x: auto; }
    .fs-document th, .fs-document td { padding: 1.5mm 2mm; text-align: start; vertical-align: top; }
    .fs-document thead th { border-bottom: 1px solid #111; font-weight: 600; }
    .fs-document tbody td { border-bottom: 1px solid #ccc; }
    .fs-document .fs-number { text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .fs-document__totals { width: auto; min-width: 70mm; margin: 4mm 0 0 auto; }
    .fs-document__totals th { font-weight: 400; }
    .fs-document .fs-document__totals td { border-bottom: 0; }
    .fs-document__totals tr:last-child th, .fs-document__totals tr:last-child td { border-top: 1px solid #111; font-weight: 600; }
    .fs-document .fs-document__notes { margin-top: 6mm; }
    .fs-document__signatures { display: flex; gap: 10mm; margin-top: 16mm; }
    .fs-document__signatures p { flex: 1; max-width: 88mm; padding-top: 1.5mm; border-top: 1px solid #111; }
    .fs-document .fs-document__statement { margin-top: 12mm; text-align: center; font-weight: 600; }
    @media print { .fs-document { padding: 0; } .fs-document tr { break-inside: avoid; } }
</style>

<article class="fs-document">
    <header class="fs-document__header">
        <div>
            <p class="fs-document__company">{{ $document['company']['name'] }}</p>
            <p>{{ $document['company']['address'] }}</p>
            <p>TIN {{ $document['company']['tin'] }}, {{ $document['company']['vat'] }}</p>
        </div>
        <div class="fs-document__title">
            <h1>{{ $document['title'] }}</h1>
            <p>{{ $document['number_label'] }} {{ $document['number'] }}</p>
        </div>
    </header>

    <div class="fs-document__parties">
        {{-- A report has no customer or supplier. --}}
        @if (filled($document['party'] ?? null))
            <section>
                <h2>{{ $document['party']['label'] }}</h2>
                <p>{{ $document['party']['name'] }}</p>
                @if (filled($document['party']['address'] ?? null))
                    <p>{{ $document['party']['address'] }}</p>
                @endif
                @if (filled($document['party']['tin'] ?? null))
                    <p>TIN {{ $document['party']['tin'] }}</p>
                @endif
            </section>
        @endif

        <dl>
            @foreach ($document['details'] ?? [] as $label => $value)
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            @endforeach
        </dl>
    </div>

    {{-- On a phone the lines scroll inside their own box, as a data table may (WCAG 1.4.10); the keyboard reaches it. --}}
    <div class="fs-document__lines" role="region" aria-label="Lines" tabindex="0">
    <table>
        <thead>
            <tr>
                @foreach ($document['columns'] as $column)
                    <th scope="col" @class(['fs-number' => in_array($column['format'] ?? 'text', ['money', 'quantity', 'unit price'], true)])>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($document['lines'] as $line)
                <tr>
                    @foreach ($document['columns'] as $key => $column)
                        <td @class(['fs-number' => in_array($column['format'] ?? 'text', ['money', 'quantity', 'unit price'], true)])>{{ $format($line[$key] ?? null, $column['format'] ?? 'text') }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @if (filled($document['totals'] ?? null))
        <table class="fs-document__totals">
            <tbody>
                @foreach ($document['totals'] as $label => $amount)
                    <tr>
                        <th scope="row">{{ $label }}</th>
                        <td class="fs-number">{{ $format($amount, 'money') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (filled($document['notes'] ?? null))
        <p class="fs-document__notes">{{ $document['notes'] }}</p>
    @endif

    @if (filled($document['signatures'] ?? null))
        <div class="fs-document__signatures">
            @foreach ($document['signatures'] as $label)
                <p>{{ $label }}</p>
            @endforeach
        </div>
    @endif

    @if (filled($document['statement'] ?? null))
        <p class="fs-document__statement">{{ $document['statement'] }}</p>
    @endif
</article>
