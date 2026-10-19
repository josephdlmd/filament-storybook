<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;

/** Stands in for the app's own payment model; its sample rows are in `SampleDatabase`. */
class Payment extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    protected $casts = ['status' => DocumentStatus::class];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** @return BelongsTo<SalesInvoice, $this> */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
