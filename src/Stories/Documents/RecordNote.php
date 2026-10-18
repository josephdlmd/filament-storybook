<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A note someone wrote on a record: what they wrote, who wrote it, and when. */
class RecordNote extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['written_at' => 'datetime'];

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** The record it is on; give each record `notes()`, a `morphMany(RecordNote::class, 'record')`. */
    public function record(): MorphTo
    {
        return $this->morphTo();
    }

    /** Who wrote it: the app's own user model. @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
