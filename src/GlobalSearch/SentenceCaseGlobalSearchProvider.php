<?php

namespace Josephdlmd\FilamentStorybook\GlobalSearch;

use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Illuminate\Support\Str;

/**
 * Filament's global search with each group headed in sentence case ("Purchase orders"). Filament heads a group with
 * the resource's plural model label, which HasSentenceCaseLabels leaves lower case so it reads right inside sentences
 * ("Delete 2 purchase orders?"). A house default binds it in place of Filament's default provider, so a panel that
 * sets its own provider keeps it (Carbon content: sentence case for headings).
 */
class SentenceCaseGlobalSearchProvider extends DefaultGlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $results = parent::getResults($query);

        return $results === null ? null : static::sentenceCase($results);
    }

    /** The same results, each group's name with a capital first letter. */
    public static function sentenceCase(GlobalSearchResults $results): GlobalSearchResults
    {
        $sentenceCased = GlobalSearchResults::make();

        foreach ($results->getCategories() as $name => $categoryResults) {
            $sentenceCased->category(Str::ucfirst($name), $categoryResults);
        }

        return $sentenceCased;
    }
}
