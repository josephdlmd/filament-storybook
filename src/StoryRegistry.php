<?php

namespace Josephdlmd\FilamentStorybook;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Finds Stories classes and describes them the way Storybook's components.json does.
 */
class StoryRegistry
{
    /** @var array<string, string> directory => namespace */
    protected array $paths = [];

    /** @var array<string, Stories>|null */
    protected ?array $stories = null;

    public function discover(string $path, string $namespace): static
    {
        $this->paths[$path] = trim($namespace, '\\');
        $this->stories = null;

        return $this;
    }

    /**
     * @return array<string, Stories> keyed by id, sorted by title
     */
    public function all(): array
    {
        if ($this->stories !== null) {
            return $this->stories;
        }

        $found = [];

        foreach ($this->paths as $path => $namespace) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $class = $namespace.'\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

                if ($file->getExtension() !== 'php'
                    || ! class_exists($class)
                    || ! is_subclass_of($class, Stories::class)
                    || (new ReflectionClass($class))->isAbstract()) {
                    continue;
                }

                $stories = app($class);
                $found[$stories->id()] = $stories;
            }
        }

        return $this->stories = collect($found)->sortBy(fn (Stories $stories): string => $stories->title())->all();
    }

    public function find(string $id): ?Stories
    {
        return $this->all()[$id] ?? null;
    }

    /** Only registered classes may be rendered, so a request can never name an arbitrary class. */
    public function findByClass(string $class): ?Stories
    {
        return collect($this->all())->first(fn (Stories $stories): bool => $stories::class === $class);
    }

    /**
     * @return array{id: string, title: string, description: string, class: class-string, path: string, imports: list<string>, stories: list<array{name: string, label: string, description: string, kind: string|null, snippet: string}>, api: list<array{method: string, signature: string, description: string}>}
     */
    public function manifest(Stories $stories): array
    {
        $file = (string) (new ReflectionClass($stories))->getFileName();
        $source = File::get($file);

        preg_match_all('/^use\s+([^;]+);/m', $source, $imports);

        $manifest = [
            'id' => $stories->id(),
            'title' => $stories->title(),
            'description' => $stories->description(),
            'class' => $stories::class,
            'path' => Str::after($file, base_path().DIRECTORY_SEPARATOR),
            'imports' => array_values(array_diff($imports[1], [Stories::class])),
            'stories' => collect($stories->stories())
                ->map(fn (ReflectionMethod $story, string $name): array => [
                    'name' => $name,
                    'label' => Str::headline($name),
                    'description' => static::docblock($story),
                    'kind' => Stories::kind($story),
                    'snippet' => static::snippet($story, $source),
                ])
                ->values()
                ->all(),
        ];

        $manifest['api'] = static::api(array_values($stories->stories()), $manifest['stories']);

        return $manifest;
    }

    /**
     * The real signatures of the methods the stories call on the component, so agents never guess an argument.
     *
     * @param  list<ReflectionMethod>  $methods
     * @param  list<array{snippet: string}>  $stories
     * @return list<array{method: string, signature: string, description: string}>
     */
    protected static function api(array $methods, array $stories): array
    {
        $api = [];

        foreach ($methods as $i => $story) {
            $type = $story->getReturnType();
            $class = $type instanceof ReflectionNamedType ? $type->getName() : null;

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            preg_match_all('/(?:->|::)(\w+)\(/', $stories[$i]['snippet'], $calls);

            // Calls inside closures on other objects, like str($state)->headline(), are not the component's.
            foreach (array_filter(array_unique($calls[1]), fn (string $name): bool => method_exists($class, $name)) as $name) {
                $method = new ReflectionMethod($class, $name);
                $key = class_basename($class).'::'.$name;

                $api[$key] ??= [
                    'method' => $key,
                    'signature' => static::signature($method),
                    'description' => (string) strtok(static::docblock($method), "\n"),
                ];
            }
        }

        return array_values($api);
    }

    protected static function signature(ReflectionMethod $method): string
    {
        $parameters = array_map(function (ReflectionParameter $parameter): string {
            $default = '';

            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
                $default = ' = '.match (true) {
                    $parameter->isDefaultValueConstant() => $parameter->getDefaultValueConstantName(),
                    $value === null => 'null',
                    is_array($value) => $value === [] ? '[]' : '[...]',
                    default => var_export($value, true),
                };
            }

            return trim($parameter->getType().' '.($parameter->isVariadic() ? '...' : '').'$'.$parameter->getName()).$default;
        }, $method->getParameters());

        return $method->getName().'('.implode(', ', $parameters).')'
            .($method->hasReturnType() ? ': '.$method->getReturnType() : '');
    }

    /** The story's body, reduced to the returned expression so it pastes straight into a schema array. */
    protected static function snippet(ReflectionMethod $story, string $source): string
    {
        $lines = array_slice(explode("\n", $source), $story->getStartLine() - 1, $story->getEndLine() - $story->getStartLine() + 1);
        $code = implode("\n", $lines);
        $body = substr($code, strpos($code, '{') + 1, strrpos($code, '}') - strpos($code, '{') - 1);

        $lines = explode("\n", rtrim($body));

        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }

        $indents = array_map(fn (string $line): int => strlen($line) - strlen(ltrim($line)), array_filter($lines, fn (string $line): bool => trim($line) !== ''));
        $indent = $indents === [] ? 0 : min($indents);
        $body = implode("\n", array_map(fn (string $line): string => substr($line, $indent), $lines));

        return preg_match('/^return\s+(.*);$/s', $body, $match) ? $match[1] : $body;
    }

    protected static function docblock(ReflectionMethod $story): string
    {
        $comment = (string) $story->getDocComment();

        return trim(preg_replace(['#^/\*\*|\*/$#', '#^\s*\* ?#m'], '', $comment));
    }
}
