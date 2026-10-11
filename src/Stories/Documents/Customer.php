<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for the app's own customer model in the record picker pattern. Its sample rows are in `SampleDatabase`.
 */
class Customer extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }
}
