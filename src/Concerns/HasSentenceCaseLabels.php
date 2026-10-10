<?php

namespace Josephdlmd\FilamentStorybook\Concerns;

use Illuminate\Support\Str;

/**
 * For a resource: sentence case where Filament title-cases the model ("Create Supplier", "Purchase Orders").
 * Filament uses the singular inside sentences (the Create page title) and the plural as a title (navigation,
 * breadcrumb, List page), so the first stays lower case and the second gets a capital first letter (Carbon content).
 * Set `$recordTitleAttribute` too: without it the record's breadcrumb is the lower-case model label. If the records have no
 * name, skip this trait and set the List page's `$title` and the resource's `$breadcrumb` instead.
 */
trait HasSentenceCaseLabels
{
    public static function getTitleCaseModelLabel(): string
    {
        return static::getModelLabel();
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
