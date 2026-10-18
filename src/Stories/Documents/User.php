<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;

/** Stands in for the app's own user model, as who did each thing in a record's history (`records-history`). */
class User extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }
}
