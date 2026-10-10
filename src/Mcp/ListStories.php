<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListStories extends Tool
{
    protected string $name = 'storybook-list';

    protected string $description = 'List this app\'s approved Filament UI patterns (stories). Call this before building any Filament form, table, infolist or action, and reuse a matching pattern via storybook-show instead of writing your own.';

    public function handle(Request $request): Response
    {
        $lines = collect(app(StoryRegistry::class)->all())
            ->map(fn (Stories $stories, string $id): string => "- `{$id}` {$stories->title()}: ".(strtok($stories->description(), "\n") ?: 'no description')
                .' (stories: '.implode(', ', array_keys($stories->stories())).')')
            ->values();

        return Response::text($lines->isEmpty()
            ? 'No stories yet. Add a class extending '.Stories::class.' to app/Stories.'
            : $lines->implode("\n"));
    }
}
