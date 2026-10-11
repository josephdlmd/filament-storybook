<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;

/** Stands in for the app's own payment model; its sample rows are in `SampleDatabase`. */
class Payment extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }
}
