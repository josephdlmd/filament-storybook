<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/** The MCP fallback for `php artisan storybook:test`, kept for one release while apps move to the command. */
#[IsReadOnly]
class TestStories extends Tool
{
    protected string $name = 'storybook-test';

    protected string $description = 'Fallback for `php artisan storybook:test`: run the command instead.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'ids' => $schema->array()->items($schema->string())->description('Pattern ids. Omit to test every story.'),
            'a11y' => $schema->boolean()->description('Also check each story against WCAG 2.2 AA with axe-core. Defaults to true.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $code = Artisan::call('storybook:test', ['ids' => (array) $request->get('ids', []), '--no-a11y' => ! $request->get('a11y', true)]);

        return $code === 0 ? Response::text(Artisan::output()) : Response::error(Artisan::output());
    }
}
