<?php

namespace Josephdlmd\FilamentStorybook;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Pages\Storybook;

/** WCAG 2.1 AA checks with axe-core in headless Chromium, shared by storybook-test and storybook-audit. */
class Accessibility
{
    /**
     * Runs axe-core on rendered HTML with the panel's theme CSS, so contrast is judged on real colours.
     *
     * @param  array<string, string>  $rendered  key => rendered HTML
     * @return array{0: list<string>, 1: string|null} the issues, and why the check was skipped
     */
    public static function check(array $rendered): array
    {
        preg_match_all('/href="([^"]+\.css[^"]*)"/', (string) Storybook::panel()?->getTheme()->getHtml()->toHtml(), $hrefs);
        $css = array_values(array_unique(array_filter(
            array_map(fn (string $url): string => public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/')), $hrefs[1]),
            'is_file',
        )));
        // Every panel page loads the house CSS too; the line-item labels depend on it.
        $css[] = __DIR__.'/../resources/css/house.css';

        $result = Process::path(base_path())->timeout(120)
            ->env(array_filter(['STORYBOOK_CHROMIUM' => config('filament-storybook.chromium')]))
            ->input((string) json_encode(['css' => $css, 'stories' => array_map(
                fn (string $key, string $html): array => ['key' => $key, 'html' => $html],
                array_keys($rendered),
                $rendered,
            )]))
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
