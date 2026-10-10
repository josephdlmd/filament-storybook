<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowInstructions extends Tool
{
    protected string $name = 'storybook-instructions';

    protected string $description = 'The rules for writing a Filament story: naming, states to cover, sample data and the test-and-fix loop. Call it before adding or changing a story.';

    public function handle(Request $request): Response
    {
        return Response::text((string) file_get_contents(__DIR__.'/../../resources/instructions/stories.md'));
    }
}
