<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/** The MCP fallback for `php artisan storybook:show`, kept for one release while apps move to the command. */
#[IsReadOnly]
class ShowStories extends Tool
{
    protected string $name = 'storybook-show';

    protected string $description = 'Fallback for `php artisan storybook:show`: run the command instead.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The pattern id, e.g. "forms-money-input".')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $code = Artisan::call('storybook:show', ['ids' => [(string) $request->get('id')]]);

        return $code === 0 ? Response::text(Artisan::output()) : Response::error(Artisan::output());
    }
}
