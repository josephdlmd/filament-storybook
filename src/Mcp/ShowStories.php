<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowStories extends Tool
{
    protected string $name = 'storybook-show';

    protected string $description = 'Show one Filament UI pattern: when to use it, the imports, and the exact PHP of every story. Copy these snippets rather than guessing component methods; only use options a story shows.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The pattern id from storybook-list, e.g. "forms-money-input".')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $id = (string) $request->get('id');
        $stories = app(StoryRegistry::class)->find($id);

        if ($stories === null) {
            return Response::error("No pattern \"{$id}\". Valid ids: ".implode(', ', array_keys(app(StoryRegistry::class)->all())).'. Never invent an id; call storybook-list to see what each one is for.');
        }

        $manifest = app(StoryRegistry::class)->manifest($stories);

        $text = "# {$manifest['title']}\n\n".($manifest['description'] ?: 'No description.')."\n\n"
            ."Defined in `{$manifest['path']}`".(($url = $this->previewUrl($id)) ? ", preview at {$url}" : '').".\n\n"
            ."```php\n".implode("\n", array_map(fn (string $import): string => "use {$import};", $manifest['imports']))."\n```\n";

        foreach ($manifest['stories'] as $story) {
            $text .= "\n## {$story['label']}\n\n".($story['description'] ? "{$story['description']}\n\n" : '')
                ."```php\n{$story['snippet']}\n```\n";
        }

        if ($manifest['api'] !== []) {
            $text .= "\n## API\n\nThe exact signatures of the methods these stories use. Pass only these arguments.\n\n```php\n"
                .implode("\n", array_map(fn (array $method): string => ($method['description'] ? "// {$method['description']}\n" : '')
                    .Str::before($method['method'], '::').'::'.$method['signature'], $manifest['api']))."\n```\n";
        }

        return Response::text($text);
    }

    protected function previewUrl(string $id): ?string
    {
        $panel = Storybook::panel();

        return $panel ? Storybook::getUrl(['component' => $id], panel: $panel->getId()) : null;
    }
}
