<?php

namespace Josephdlmd\FilamentStorybook\Stories\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Josephdlmd\FilamentStorybook\Stories\Documents\WithholdingCode;

/** Stands in for the app's own model in the import and export pattern. */
class Supplier extends Model
{
    protected $guarded = [];

    /** The ATC withheld from the supplier by default, changeable on each purchase. */
    public function withholdingCode(): BelongsTo
    {
        return $this->belongsTo(WithholdingCode::class, 'atc');
    }
}
