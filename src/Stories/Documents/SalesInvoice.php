<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stands in for the app's own sales invoice model, so patterns can use a real relationship and check numbers against real rows (`SampleDatabase`). */
class SalesInvoice extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

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
