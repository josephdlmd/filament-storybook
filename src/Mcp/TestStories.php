<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Facades\Filament;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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

        foreach ($ids === [] ? $all : array_intersect_key($all, array_flip($ids)) as $id => $stories) {
            foreach ($stories->stories() as $name => $story) {
                if (Stories::kind($story) === null) {
                    $failing["{$id}.{$name}"] = 'Not a story: declare a Filament schema component, table column or action as its return type, at '
                        .static::relative((string) $story->getFileName()).':'.$story->getStartLine();

                    continue;
                }

                try {
                    Livewire::mount(StoryCanvas::class, ['stories' => $stories::class, 'story' => $name]);
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

        $text = '# '.count($passing).' passing, '.count($failing)." failing\n";

        if ($failing !== []) {
            $text .= "\n## Failing\n\n"
                .implode("\n", array_map(fn (string $story, string $error): string => "- `{$story}`: {$error}", array_keys($failing), $failing))
                ."\n\nFix each failure, then run storybook-test again until every story passes.\n";
        }

        return Response::text($text.($passing === [] ? '' : "\n## Passing\n\n".implode("\n", array_map(fn (string $story): string => "- `{$story}`", $passing))."\n"));
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
