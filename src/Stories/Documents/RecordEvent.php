<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One line of a record's history: what happened to it (created, posted, approved, rejected, voided), who did it, when, and why. */
class RecordEvent extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['happened_at' => 'datetime'];

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** The document it happened to; give each document `events()`, a `morphMany(RecordEvent::class, 'record')`. */
    public function record(): MorphTo
    {
        return $this->morphTo();
    }
}
