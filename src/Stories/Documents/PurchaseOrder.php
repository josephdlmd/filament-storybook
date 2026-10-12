<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;

/** Stands in for the app's own purchase order model in the purchases and payables report; its sample rows are in `SampleDatabase`. */
class PurchaseOrder extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }
}
