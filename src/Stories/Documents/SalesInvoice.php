<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Spatie\Activitylog\Models\Activity;

/** Stands in for the app's own sales invoice model, so patterns can use a real relationship and check numbers against real rows (`SampleDatabase`). */
class SalesInvoice extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

    protected $casts = ['status' => DocumentStatus::class];

    public $timestamps = false;

    protected static function booted(): void
    {
        SampleDatabase::boot();
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Its key events in the activity log (`records-history`). Not spatie's LogsActivity, which would log every save. @return MorphMany<Activity, $this> */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /** @return MorphMany<RecordNote, $this> */
    public function notes(): MorphMany
    {
        return $this->morphMany(RecordNote::class, 'record');
    }

    /** Whether a payment against it is not void; it can't be voided until none is. Asked fresh each time, never from a count loaded with the page. */
    public function hasLivePayments(): bool
    {
        return $this->payments()->where('status', '!=', DocumentStatus::Void)->exists();
    }
}
