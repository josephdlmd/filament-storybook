<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Facades\Filament;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Accessibility;
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
                    $failing["{$id}.{$name}"] = 'Not a story: return a Filament component, table column or action, or take and return a Table or Schema, at '
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

        [$issues, $skipped] = $request->get('a11y', true) && $rendered !== [] ? Accessibility::check($rendered) : [[], null];

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
                $warnings[] = "{$key}: no placeholder(); put the example in helperText(), worded \"For example, …\" (USWDS, GOV.UK).";
            }

            if (preg_match_all("/->helperText\\('([^']*)'/", $story['snippet'], $hints)) {
                foreach ($hints[1] as $hint) {
                    if (preg_match('/\\.(\\s|$)|\\b(e\\.g|i\\.e|etc)\\b/i', $hint)) {
                        $warnings[] = "{$key}: helper text is one short line with no full stop and no \"e.g.\" or \"etc\"; say \"For example, …\" (GOV.UK text input).";
                    }
                }
            }

            if (str_contains($story['snippet'], '->native(false)')) {
                $warnings[] = "{$key}: native(false) makes a date that cannot be typed; use the native picker (Carbon and USWDS date pickers).";
            }

            if (str_contains($story['snippet'], 'Toggle::make(')) {
                $warnings[] = "{$key}: a toggle is only for something that applies at once; a yes/no saved with a form is a Checkbox (Carbon toggle and checkbox).";
            }

            if (str_contains($story['snippet'], 'FileUpload::make(') && (! preg_match('/->(acceptedFileTypes|image)\\(/', $story['snippet']) || ! str_contains($story['snippet'], '->maxSize('))) {
                $warnings[] = "{$key}: an upload states its accepted types and largest size (USWDS file input).";
            }

            if (str_contains($story['snippet'], '->hiddenLabel(')) {
                $warnings[] = "{$key}: keep the visible label; hiddenLabel() leaves the field unnamed on screen (Carbon forms).";
            }

            if (preg_match("/->(label|modalSubmitActionLabel)\\('(ok|okay|yes|done|submit|confirm)'\\)/i", $story['snippet'], $vague)) {
                $warnings[] = "{$key}: a button labelled \"{$vague[2]}\" doesn't say what it does; use a verb, such as \"Approve order\" (Carbon modal and forms).";
            }

            if (preg_match_all("/(?:->(?:label|heading|modalHeading)|Section::make)\\('([^']+)'/", $story['snippet'], $texts)) {
                foreach (array_filter($texts[1], fn (string $text): bool => AuditPages::isTitleCase($text, $text)) as $text) {
                    $warnings[] = "{$key}: \"{$text}\" is in Title Case; write labels and headings in sentence case (Carbon content).";
                }
            }

            if (preg_match("/->title\\('[^']*\\.'\\)/", $story['snippet'])) {
                $warnings[] = "{$key}: a notification title has no full stop (Carbon notification).";
            }

            if (in_array($story['kind'], ['column', 'table'], true) && $stories->records() === []) {
                $warnings[] = "{$key}: add sample rows in records(), so the table shows real values.";
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
