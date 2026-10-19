<?php

namespace Josephdlmd\FilamentStorybook;

/**
 * Strict mode: every check that finds something fails, unless the owner settled it as an exception in the app's
 * config/storybook-exceptions.php. Each entry names the check's id, its target (the page or story key the report
 * names, and a field or column if the check is about one), the reason, and who approved it and when:
 *
 *     ['check' => 'forms.read-only-saved', 'target' => 'EditInvoice', 'field' => 'total', 'reason' => '…', 'approved' => 'owner, 2026-10-15']
 *
 * Accessibility issues are never settled here: WCAG always wins.
 */
class SettledExceptions
{
    /**
     * Splits check lines ("- `target` `check`: message") into failing and settled ones. On a full run, an entry for
     * this tool that settles nothing fails too, so a fixed screen doesn't keep a stale exception.
     *
     * @param  list<string>  $lines
     * @return array{0: list<string>, 1: list<string>}
     */
    public static function apply(array $lines, bool $stories, bool $full): array
    {
        // storybook:test's checks are story.*; storybook-audit's are the rest.
        [$entries, $incomplete] = collect((array) config('storybook-exceptions', []))
            ->filter(fn (mixed $entry): bool => is_array($entry) && str_starts_with((string) ($entry['check'] ?? ''), 'story.') === $stories)
            ->partition(fn (array $entry): bool => filled($entry['target'] ?? null) && filled($entry['reason'] ?? null) && filled($entry['approved'] ?? null));

        $failing = $incomplete->map(fn (array $entry): string => "- `config/storybook-exceptions.php` `exceptions.incomplete`: the entry for `{$entry['check']}` needs its target, reason, and who approved it and when.")->values()->all();
        $settled = [];
        $used = [];

        foreach ($lines as $line) {
            preg_match('/^- `([^`]+)` `([^`]+)`: /', $line, $match);
            $index = $match === [] ? false : $entries->search(fn (array $entry): bool => $entry['check'] === $match[2] && $entry['target'] === $match[1]
                && (blank($entry['field'] ?? null) || str_contains($line, '`'.$entry['field'].'`') || str_contains($line, '"'.$entry['field'].'"')));

            if ($index === false) {
                $failing[] = $line;

                continue;
            }

            $used[$index] = true;
            $settled[] = "{$line} Settled: {$entries[$index]['reason']} ({$entries[$index]['approved']}).";
        }

        foreach ($full ? $entries->diffKeys($used) : [] as $entry) {
            $failing[] = "- `{$entry['target']}` `exceptions.stale`: the settled exception for `{$entry['check']}` matches nothing; remove it from config/storybook-exceptions.php.";
        }

        return [$failing, $settled];
    }
}
