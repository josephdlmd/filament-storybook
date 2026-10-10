<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gadget extends Model
{
    protected $guarded = [];

    public function parts(): HasMany
    {
        return $this->hasMany(GadgetPart::class);
    }
}
