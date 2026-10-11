<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Stands in for the app's own sales invoice model, so patterns can use a real relationship and check numbers against real rows (`SampleDatabase`). */
class SalesInvoice extends Model
{
    protected $connection = 'storybook';

    protected $guarded = [];

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

    /** Whether a payment against it is not void; it can't be voided until none is. Asked fresh each time, never from a count loaded with the page. */
    public function hasLivePayments(): bool
    {
        return $this->payments()->where('status', '!=', 'void')->exists();
    }
}
