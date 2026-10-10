<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Facades\Filament;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Livewire\Livewire;
use ReflectionClass;
use Throwable;

#[IsReadOnly]
class TestStories extends Tool
{
    protected string $name = 'storybook-test';

    protected string $description = 'Render stories through the real Storybook canvas and report which pass and which fail, with the error and the line that caused it. Run it after adding or changing a story, or a component or enum a story uses. Fix every failure and run it again until all pass.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'ids' => $schema->array()->items($schema->string())->description('Pattern ids from storybook-list. Omit to test every story.'),
            'a11y' => $schema->boolean()->description('Also check each story against WCAG 2.1 AA with axe-core. Defaults to true.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $all = app(StoryRegistry::class)->all();
        $ids = array_values(array_filter((array) $request->get('ids', [])));

        if ($unknown = array_diff($ids, array_keys($all))) {
            return Response::error('Unknown pattern ids: '.implode(', ', $unknown).'. Valid ids: '.implode(', ', array_keys($all)).'.');
        }

        // Fields and columns may read the current panel while rendering, which a console process lacks.
        if ($panel = Storybook::panel()) {
            Filament::setCurrentPanel($panel);
        }

        $passing = [];
        $failing = [];
        $warnings = [];
        $rendered = [];

        foreach ($ids === [] ? $all : array_intersect_key($all, array_flip($ids)) as $id => $stories) {
            $warnings = [...$warnings, ...static::warnings($id, $stories, app(StoryRegistry::class)->manifest($stories))];

            foreach ($stories->stories() as $name => $story) {
                if (Stories::kind($story) === null) {
                    $failing["{$id}.{$name}"] = 'Not a story: declare a Filament schema component, table column or action as its return type, at '
                        .static::relative((string) $story->getFileName()).':'.$story->getStartLine();

                    continue;
                }

                try {
                    $rendered["{$id}.{$name}"] = (string) Livewire::mount(StoryCanvas::class, ['stories' => $stories::class, 'story' => $name]);
                    $passing[] = "{$id}.{$name}";
                } catch (Throwable $e) {
                    // Render errors arrive wrapped in view exceptions; the innermost one names the actual mistake.
                    while ($e->getPrevious() !== null) {
                        $e = $e->getPrevious();
                    }

                    $failing["{$id}.{$name}"] = class_basename($e).': '.$e->getMessage().' at '.static::location($e, $stories);
                }
            }
        }

        [$issues, $skipped] = $request->get('a11y', true) && $rendered !== [] ? static::accessibility($rendered) : [[], null];

        $text = '# '.count($passing).' passing, '.count($failing).' failing, '.count($warnings).' warnings, '.count($issues)." accessibility issues\n"
            .($skipped ? "\n{$skipped}\n" : '');

        if ($failing !== []) {
            $text .= "\n## Failing\n\n"
                .implode("\n", array_map(fn (string $story, string $error): string => "- `{$story}`: {$error}", array_keys($failing), $failing))
                ."\n\nFix each failure, then run storybook-test again until every story passes.\n";
        }

        if ($issues !== []) {
            $text .= "\n## Accessibility\n\nThese stories break a WCAG 2.1 AA rule (axe-core). Fix each one, then run storybook-test again.\n\n"
                .implode("\n", $issues)."\n";
        }

        if ($warnings !== []) {
            $text .= "\n## Warnings\n\nThese stories render but break a house rule. Fix each one too, or tell the user why it should stay.\n\n"
                .implode("\n", array_map(fn (string $warning): string => "- {$warning}", $warnings))."\n";
        }

        return Response::text($text.($passing === [] ? '' : "\n## Passing\n\n".implode("\n", array_map(fn (string $story): string => "- `{$story}`", $passing))."\n"));
    }

    /**
     * Runs axe-core on the rendered stories with the panel's theme CSS, so contrast is judged on real colours.
     *
     * @param  array<string, string>  $rendered  story key => canvas HTML
     * @return array{0: list<string>, 1: string|null} the issues, and why the check was skipped
     */
    protected static function accessibility(array $rendered): array
    {
        preg_match_all('/href="([^"]+\.css[^"]*)"/', (string) Storybook::panel()?->getTheme()->getHtml()->toHtml(), $hrefs);
        $css = array_values(array_unique(array_filter(
            array_map(fn (string $url): string => public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/')), $hrefs[1]),
            'is_file',
        )));

        $result = Process::path(base_path())->timeout(120)
            ->env(array_filter(['STORYBOOK_CHROMIUM' => getenv('STORYBOOK_CHROMIUM') ?: env('STORYBOOK_CHROMIUM')]))
            ->input((string) json_encode(['css' => $css, 'stories' => array_map(
                fn (string $key, string $html): array => ['key' => $key, 'html' => $html],
                array_keys($rendered),
                $rendered,
            )]))
            ->run(['node', __DIR__.'/../../resources/a11y/axe.mjs']);

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

    /**
     * House rules a story can break and still render. Agents copy stories, so a broken rule spreads to every screen.
     *
     * @param  array{description: string, stories: list<array{name: string, description: string, kind: string|null, snippet: string}>}  $manifest
     * @return list<string>
     */
    protected static function warnings(string $id, Stories $stories, array $manifest): array
    {
        $warnings = [];

        if (blank($manifest['description'])) {
            $warnings[] = "`{$id}`: add description(), saying when to use this pattern and when not to.";
        }

        foreach ($manifest['stories'] as $i => $story) {
            $key = "`{$id}.{$story['name']}`";

            if ($i > 0 && blank($story['description'])) {
                $warnings[] = "{$key}: add a one-line docblock saying how this variant differs.";
            }

            if (str_contains($story['snippet'], '->placeholder(')) {
                $warnings[] = "{$key}: format examples never go in placeholder(); use mask() for a fixed shape, otherwise a one-line helperText().";
            }

            if (str_contains($story['snippet'], '->hiddenLabel(')) {
                $warnings[] = "{$key}: keep the visible label; hiddenLabel() leaves the field unnamed on screen.";
            }

            if ($story['kind'] === 'column' && $stories->records() === []) {
                $warnings[] = "{$key}: add sample rows in records(), so the column shows real values.";
            }
        }

        return $warnings;
    }

    /** Points at the story's own line when the error passed through it, since that is what the agent can fix. */
    protected static function location(Throwable $e, Stories $stories): string
    {
        $file = (new ReflectionClass($stories))->getFileName();
        $frame = collect([['file' => $e->getFile(), 'line' => $e->getLine()], ...$e->getTrace()])
            ->first(fn (array $frame): bool => ($frame['file'] ?? null) === $file)
            ?? ['file' => $e->getFile(), 'line' => $e->getLine()];

        return static::relative($frame['file']).':'.$frame['line'];
    }

    protected static function relative(string $file): string
    {
        return Str::after($file, base_path().DIRECTORY_SEPARATOR);
    }
}
