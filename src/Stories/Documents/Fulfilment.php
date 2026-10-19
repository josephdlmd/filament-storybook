<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;

/**
 * Turning one document into the next (owner's business standards): a posted quotation into an order, a posted order into
 * one or more deliveries, and each posted delivery into one invoice. The next document's lines are copied from the one
 * before with their prices, and a quantity can only go down, to what is left. Quantities have 3 decimals. Copy it into
 * the app.
 */
class Fulfilment
{
    /** What is left of a line: its quantity less what posted later documents have taken from it. */
    public static function remaining(string $quantity, string $taken): string
    {
        return bcsub($quantity, $taken, 3);
    }

    /**
     * An order's delivery status from its lines: Not delivered, Partly delivered, or Delivered once every line is.
     *
     * @param  iterable<array<string, mixed>|Model>  $lines  Each with its `quantity` and `delivered`.
     */
    public static function status(iterable $lines): string
    {
        $lines = collect($lines);
        $delivered = fn (mixed $line): string => (string) (data_get($line, 'delivered') ?? '0');

        return match (true) {
            $lines->every(fn (mixed $line): bool => bccomp(static::remaining((string) data_get($line, 'quantity'), $delivered($line)), '0', 3) <= 0) => 'Delivered',
            $lines->every(fn (mixed $line): bool => bccomp($delivered($line), '0', 3) === 0) => 'Not delivered',
            default => 'Partly delivered',
        };
    }

    /**
     * The next document's lines: each line of the one before with its description and unit price (and any tax class the app keeps on a line), the
     * quantity given for it, and the line it came from. A line given 0 is left out. Refuse a quantity over what is left
     * before calling this, inside the transaction that saves the next document, with the source's lines locked.
     *
     * @param  iterable<array<string, mixed>|Model>  $lines
     * @param  array<int|string, string>  $quantities  Keyed by the source line's id.
     * @return list<array<string, mixed>>
     */
    public static function nextLines(iterable $lines, array $quantities): array
    {
        return collect($lines)
            ->map(fn (mixed $line): array => [
                'source_line_id' => data_get($line, 'id'),
                'description' => data_get($line, 'description'),
                'unit_price' => data_get($line, 'unit_price'),
                'quantity' => (string) ($quantities[data_get($line, 'id')] ?? '0'),
            ])
            ->filter(fn (array $line): bool => bccomp($line['quantity'], '0', 3) === 1)
            ->values()
            ->all();
    }
}
