<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Support\Number;

/**
 * A document's money, worked out the one way the books use: decimal strings, never floats, and each figure rounded
 * once, half up. The form shows these and the server works them out again when saving, so the two always agree.
 * Unit prices include VAT. Copy it into the app, for example as `app/Support/DocumentTotals.php`.
 */
class DocumentTotals
{
    /** The VAT classes a line can have, keyed as stored. */
    public const VAT_CLASSES = ['vatable' => '12%', 'zero_rated' => 'Zero-rated', 'exempt' => 'Exempt'];

    /**
     * Subtotals per VAT class, the VAT on the VATable total (12/112 of it, since prices include VAT) and the total due.
     *
     * @param  iterable<array<string, mixed>>  $lines  Each with `quantity`, `unit_price` and `vat_class`; incomplete lines count as nothing.
     * @return array{vatable_sales: string, vat: string, zero_rated_sales: string, exempt_sales: string, total: string}
     */
    public static function of(iterable $lines): array
    {
        $sums = array_fill_keys(array_keys(self::VAT_CLASSES), '0.00');

        foreach ($lines as $line) {
            $total = static::lineTotal($line['quantity'] ?? null, $line['unit_price'] ?? null);

            if ($total !== null && isset($sums[$line['vat_class'] ?? null])) {
                $sums[$line['vat_class']] = bcadd($sums[$line['vat_class']], $total, 2);
            }
        }

        $vat = static::round(bcdiv(bcmul($sums['vatable'], '12', 2), '112', 10));

        return [
            'vatable_sales' => bcsub($sums['vatable'], $vat, 2),
            'vat' => $vat,
            'zero_rated_sales' => $sums['zero_rated'],
            'exempt_sales' => $sums['exempt'],
            'total' => bcadd(bcadd($sums['vatable'], $sums['zero_rated'], 2), $sums['exempt'], 2),
        ];
    }

    /** Quantity × unit price, rounded once to centavos; null until both are numbers. */
    public static function lineTotal(mixed $quantity, mixed $unitPrice): ?string
    {
        if (! is_numeric($quantity) || ! is_numeric($unitPrice)) {
            return null;
        }

        // Filament keeps numbers as floats; formatting each to its own decimals gives back exactly what was typed.
        return static::round(bcmul(number_format((float) $quantity, 3, '.', ''), number_format((float) $unitPrice, 4, '.', ''), 7));
    }

    /**
     * The tax withheld from a supplier's bill: the rate of its ATC on the bill's total less VAT, worked out once when
     * the bill is posted. Payments then settle the net payable, and the purchase order's BIR Form 2307 shows this figure.
     */
    public static function withholding(string $total, string $vat, string $rate): string
    {
        return static::round(bcmul(bcsub($total, $vat, 2), $rate, 6));
    }

    /**
     * What a customer is expected to withhold from a payment of `$amount` against an invoice: the rate on the
     * VAT-exclusive share of it. A proposal only; the user records what the customer's BIR Form 2307 shows.
     */
    public static function expectedWithholding(string $amount, string $total, string $vat, string $rate): string
    {
        if (bccomp($total, '0', 2) === 0) {
            return '0.00';
        }

        return static::round(bcdiv(bcmul(bcmul($amount, bcsub($total, $vat, 2), 4), $rate, 6), $total, 10));
    }

    /** An amount as it is written on a check or a receipt: "Six thousand two hundred sixty-six pesos and 41/100". */
    public static function inWords(string $amount): string
    {
        [$pesos, $centavos] = explode('.', static::round($amount));

        return ucfirst(Number::spell((int) $pesos)).' '.($pesos === '1' ? 'peso' : 'pesos')." and {$centavos}/100";
    }

    /** Half up to centavos, away from zero for a negative, so a credit mirrors its sale. */
    public static function round(string $exact): string
    {
        return str_starts_with($exact, '-') ? bcsub($exact, '0.005', 2) : bcadd($exact, '0.005', 2);
    }
}
