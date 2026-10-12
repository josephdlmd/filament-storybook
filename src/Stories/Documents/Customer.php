<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stands in for the app's own customer model in the record picker and archive patterns. Its sample rows are in
 * `SampleDatabase`. A customer used on a posted document is archived, never deleted (`SoftDeletes`; `actions-archive`).
 */
class Customer extends Model
{
    use SoftDeletes;

    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** @return HasMany<SalesInvoice, $this> */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    /** @return HasMany<SalesOrder, $this> */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }
}
