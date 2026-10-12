<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;

/** One line of an item's stock history: what came in or went out, on which document, and what was on hand after. */
class StockMovement extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    /** Quantities to 3 decimals and costs to 4, as strings, so arithmetic stays exact (bcmath). */
    protected $casts = ['moved_on' => 'date', 'quantity' => 'decimal:3', 'unit_cost' => 'decimal:4', 'on_hand' => 'decimal:3'];

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }
}
