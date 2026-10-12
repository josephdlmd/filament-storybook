<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Stands in for the app's own item model in the stock patterns; its sample rows are in `SampleDatabase`. */
class Item extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    /** Quantities to 3 decimals and costs to 4, as strings, so arithmetic stays exact (bcmath). */
    protected $casts = ['on_hand' => 'decimal:3', 'average_cost' => 'decimal:4', 'reorder_level' => 'decimal:3'];

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
