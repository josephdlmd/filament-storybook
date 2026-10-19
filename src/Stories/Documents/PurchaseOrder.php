<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;

/** Stands in for the app's own purchase order model in the purchases and payables reports; its sample rows are in `SampleDatabase`. */
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
}
