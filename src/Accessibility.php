<?php

namespace Josephdlmd\FilamentStorybook;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Pages\Storybook;

/** WCAG 2.2 AA checks with axe-core in headless Chromium, shared by storybook-test and storybook-audit. */
class Accessibility
{
    /**
     * Runs axe-core on rendered HTML with the panel's theme CSS, so contrast is judged on real colours, and checks that
     * nothing is cut off or scrolls the page sideways at 320 CSS pixels (WCAG 1.4.10). An open modal in the HTML is shown.
     *
     * @param  array<string, string>  $rendered  key => rendered HTML of a page's content or a story
     * @param  array<string, string>  $pages  key => a whole page, checked whole: its title, language, landmarks and skip link
     * @return array{0: list<string>, 1: string|null} the issues, and why the check was skipped
     */
    public static function check(array $rendered, array $pages = []): array
    {
        preg_match_all('/href="([^"]+\.css[^"]*)"/', (string) Storybook::panel()?->getTheme()->getHtml()->toHtml(), $hrefs);
        $css = array_values(array_unique(array_filter(
            array_map(fn (string $url): string => public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/')), $hrefs[1]),
            'is_file',
        )));
        // Every panel page loads the house CSS too; the line-item labels depend on it.
        $css[] = __DIR__.'/../resources/css/house.css';

        $plugin = Storybook::panel()?->getPlugins()['filament-storybook'] ?? null;

        if ($plugin?->hasCompactTables()) {
            $css[] = __DIR__.'/../resources/css/compact-tables.css';
        }

        if ($plugin?->hasCompact()) {
            $css[] = __DIR__.'/../resources/css/compact.css';
        }

        $result = Process::path(base_path())->timeout(120)
            ->env(array_filter(['STORYBOOK_CHROMIUM' => config('filament-storybook.chromium')]))
            ->input((string) json_encode(['css' => $css, 'stories' => [
                ...array_map(fn (string $key, string $html): array => ['key' => $key, 'html' => $html], array_keys($rendered), $rendered),
                ...array_map(fn (string $key, string $html): array => ['key' => $key, 'html' => $html, 'page' => true], array_keys($pages), $pages),
            ]]))
            ->run(['node', __DIR__.'/../resources/a11y/axe.mjs']);

        if ($result->failed()) {
            $reason = Str::of($result->errorOutput())->explode("\n")->first(fn (string $line): bool => str_contains($line, 'Error')) ?? 'node could not run';

            return [[], 'Accessibility not checked ('.Str::limit(trim($reason), 160).'). To enable it, run `npm install --save-dev playwright-core axe-core` and `npx playwright-core install chromium`.'];
        }

        $issues = [];

        foreach ((array) json_decode($result->output(), true) as $story => $violations) {
            foreach ($violations as $violation) {
                $issues[] = "- `{$story}`: [{$violation['impact']}] {$violation['id']}: {$violation['help']} (".implode(', ', $violation['elements']).')';
            }
        }

        return [$issues, null];
    }
}
