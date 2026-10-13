<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stands in for the app's own purchase order model in the purchases, payables and withholding reports; its sample rows are in `SampleDatabase`. */
class PurchaseOrder extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    protected $casts = ['status' => DocumentStatus::class];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** @return BelongsTo<WithholdingCode, $this> */
    public function withholdingCode(): BelongsTo
    {
        return $this->belongsTo(WithholdingCode::class, 'withholding_code', 'code');
    }
}
