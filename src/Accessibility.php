<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Panel;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Pages\Storybook;

/** WCAG 2.2 AA checks with axe-core in headless Chromium, shared by storybook:test and storybook-audit. */
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
    public static function check(array $rendered, array $pages = [], ?Panel $panel = null): array
    {
        $panel ??= Storybook::panel();
        preg_match_all('/href="([^"]+\.css[^"]*)"/', (string) $panel?->getTheme()->getHtml()->toHtml(), $hrefs);
        $css = [];
        $downloaded = [];

        foreach (array_unique($hrefs[1]) as $url) {
            $file = public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/'));

            if (is_file($file)) {
                $css[] = $file;
            } elseif (str_starts_with($url, 'http') && filled($body = rescue(fn (): string => Http::timeout(10)->get($url.(str_contains($url, '?') ? '&' : '?').'direct')->throw()->body(), '', report: false))) {
                // While `npm run dev` runs, the theme comes from Vite's dev server; `?direct` asks for the CSS, not its script wrapper.
                file_put_contents($css[] = $downloaded[] = sys_get_temp_dir().'/fs-theme-'.Str::random(16).'.css', $body);
            }
        }

        // Dark mode is checked only under a theme, which gives the page its dark background.
        $themed = $css !== [];

        // Every panel page loads the house and compact CSS too; the line-item labels depend on it.
        $css[] = __DIR__.'/../resources/css/house.css';
        $css[] = __DIR__.'/../resources/css/compact.css';

        // And the panel's colours, which Filament writes into each page's head rather than the theme, so a story is checked in them.
        if ($panel) {
            FilamentColor::register($panel->getColors());
            $colors = collect(FilamentColor::getColors())->flatMap(fn (array $palette, string $name): array => collect($palette)->map(fn (string $value, int|string $shade): string => "--{$name}-{$shade}: {$value};")->all());
            file_put_contents($css[] = $downloaded[] = sys_get_temp_dir().'/fs-colors-'.Str::random(16).'.css', ':root { '.$colors->implode(' ').' }');
        }

        try {
            // Under Boost's limit of 180 seconds a call, with time left for rendering the pages.
            $result = Process::path(base_path())->timeout(90)
                ->env(array_filter(['STORYBOOK_CHROMIUM' => config('filament-storybook.chromium')]))
                // And the house script, so what it fixes in the page (names, roles) is checked as the browser gets it.
                ->input((string) json_encode(['css' => $css, 'dark' => $themed, 'js' => [__DIR__.'/../resources/js/house.js'], 'stories' => [
                    ...array_map(fn (string $key, string $html): array => ['key' => $key, 'html' => $html], array_keys($rendered), $rendered),
                    ...array_map(fn (string $key, string $html): array => ['key' => $key, 'html' => $html, 'page' => true], array_keys($pages), $pages),
                ]]))
                ->run(['node', __DIR__.'/../resources/a11y/axe.mjs']);
        } catch (ProcessTimedOutException) {
            return [[], 'Accessibility not checked (axe-core took over 90 seconds). Check fewer pages or stories at a time.'];
        } finally {
            array_map('unlink', $downloaded);
        }

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
