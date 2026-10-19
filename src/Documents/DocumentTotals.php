<?php

namespace Josephdlmd\FilamentStorybook\Documents;

/**
 * A document's money, worked out the one way the books use: decimal strings, never floats, and each figure rounded
 * once, half up. The form shows these and the server works them out again when saving, so the two always agree.
 * Call it from the package; filament-storybook-ph extends it with VAT and withholding.
 */
class DocumentTotals
{
    /**
     * The total of a document's lines.
     *
     * @param  iterable<array<string, mixed>>  $lines  Each with `quantity` and `unit_price`; incomplete lines count as nothing.
     * @return array{total: string}
     */
    public static function of(iterable $lines): array
    {
        $total = '0.00';

        foreach ($lines as $line) {
            $total = bcadd($total, static::lineTotal($line['quantity'] ?? null, $line['unit_price'] ?? null) ?? '0.00', 2);
        }

        return ['total' => $total];
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

    /** Half up to centavos, away from zero for a negative, so a credit mirrors its sale. */
    public static function round(string $exact): string
    {
        return str_starts_with($exact, '-') ? bcsub($exact, '0.005', 2) : bcadd($exact, '0.005', 2);
    }
}
