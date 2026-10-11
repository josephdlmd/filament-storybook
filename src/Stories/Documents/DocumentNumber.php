<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gives a document its number when it is posted, such as SI-000124: a prefix per document type and a 6-digit series
 * that never resets, with no gaps and no number used twice. Copy it into the app, with a `document_numbers` table
 * (`prefix` unique, `last` unsigned integer) whose migration inserts one row per prefix, starting at 0.
 */
class DocumentNumber
{
    /** Call it inside the transaction that posts the document, so a posting that fails gives its number back. */
    public static function next(string $prefix): string
    {
        // The row lock makes a second posting wait for the first, so two users posting at once never share a number.
        $last = DB::table('document_numbers')->where('prefix', $prefix)->lockForUpdate()->value('last');

        if ($last === null) {
            throw new RuntimeException("There is no number series for {$prefix}; add its row to document_numbers.");
        }

        DB::table('document_numbers')->where('prefix', $prefix)->update(['last' => $last + 1]);

        return sprintf('%s-%06d', $prefix, $last + 1);
    }
}
