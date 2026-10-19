<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;

/** Stands in for the app's own sales order model in the credit limit pattern; its sample rows are in `SampleDatabase`. */
class SalesOrder extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    protected $casts = ['status' => DocumentStatus::class];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
