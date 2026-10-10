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

    protected string $description = 'How to write a Filament story, and every UI rule with its source (forms, feedback and dialogs, tables, layout, accessibility, sign-in). Call it before adding or changing a story, and when unsure how a screen should behave.';

    public function handle(Request $request): Response
    {
        return Response::text(file_get_contents(__DIR__.'/../../resources/instructions/stories.md')
            ."\n---\n\n".file_get_contents(__DIR__.'/../../docs/rules.md'));
    }
}
