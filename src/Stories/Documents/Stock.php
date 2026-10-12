<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Stock on hand in the one warehouse, valued at weighted average cost (owner's business standards): a receipt adds at
 * its unit cost and moves the average, an issue or adjustment takes out at the average, and nothing takes stock below
 * zero. Each movement is a row of `stock_movements`, so an item's history adds up to what is on hand. Quantities have 3
 * decimals and costs 4, rounded half up. Call these inside the transaction that posts the document, which they lock
 * the item in. Copy it into the app, with its item and movement models.
 */
class Stock
{
    /** A receiving report's line: adds to stock and moves the average cost. */
    public static function receive(Model $item, string $quantity, string $unitCost, string $document): void
    {
        $item = static::lock($item);
        $onHand = bcadd((string) $item->on_hand, $quantity, 3);
        $value = bcadd(bcmul((string) $item->on_hand, (string) $item->average_cost, 7), bcmul($quantity, $unitCost, 7), 7);

        $item->update(['on_hand' => $onHand, 'average_cost' => bccomp($onHand, '0', 3) === 1 ? static::roundCost(bcdiv($value, $onHand, 8)) : $unitCost]);
        static::record($item, $quantity, $unitCost, $document);
    }

    /** A delivery's line: takes stock out at the average cost; refused if there isn't enough. */
    public static function issue(Model $item, string $quantity, string $document): void
    {
        static::adjust($item, bcmul($quantity, '-1', 3), $document);
    }

    /** An approved adjustment: a count correction up or down, or stock damaged, lost or expired; never below zero. */
    public static function adjust(Model $item, string $change, string $document): void
    {
        $item = static::lock($item);
        $onHand = bcadd((string) $item->on_hand, $change, 3);

        if (bccomp($onHand, '0', 3) === -1) {
            throw new RuntimeException("Only {$item->on_hand} {$item->unit} of {$item->name} on hand");
        }

        $item->update(['on_hand' => $onHand]);
        static::record($item, $change, (string) $item->average_cost, $document);
    }

    /** At or below its reorder level. */
    public static function isLow(Model $item): bool
    {
        return $item->reorder_level !== null && bccomp((string) $item->on_hand, (string) $item->reorder_level, 3) <= 0;
    }

    /** The item read again, locked until the transaction ends, so two postings at once can't both take the last unit. */
    protected static function lock(Model $item): Model
    {
        if (DB::connection($item->getConnectionName())->transactionLevel() === 0) {
            throw new RuntimeException('Move stock inside the transaction that posts the document.');
        }

        return $item->newQuery()->lockForUpdate()->findOrFail($item->getKey());
    }

    protected static function record(Model $item, string $quantity, string $unitCost, string $document): void
    {
        $item->stockMovements()->create(['document' => $document, 'moved_on' => today(), 'quantity' => $quantity, 'unit_cost' => $unitCost, 'on_hand' => $item->on_hand]);
    }

    protected static function roundCost(string $exact): string
    {
        return bcadd($exact, '0.00005', 4);
    }
}
