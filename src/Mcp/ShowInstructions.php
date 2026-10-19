<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/** The MCP fallback for `php artisan storybook:instructions`, kept for one release while apps move to the command. */
#[IsReadOnly]
class ShowInstructions extends Tool
{
    protected string $name = 'storybook-instructions';

    protected string $description = 'Fallback for `php artisan storybook:instructions`: run the command instead.';

    public function handle(Request $request): Response
    {
        $code = Artisan::call('storybook:instructions');

        return $code === 0 ? Response::text(Artisan::output()) : Response::error(Artisan::output());
    }
}
