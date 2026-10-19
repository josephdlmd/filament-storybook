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
                    'label' => Str::ucfirst(Str::snake($name, ' ')),
                    'description' => static::docblock($story),
                    'kind' => Stories::kind($story),
                    'states' => Stories::states($story),
                    'snippet' => static::snippet($story, $source),
                ])
                ->values()
                ->all(),
        ];

        $manifest['api'] = static::api(array_values($stories->stories()), $manifest['stories'], $manifest['imports']);

        return $manifest;
    }

    /**
     * The real signatures of the Filament methods the stories call, so agents never guess an argument.
     * Each call chain is followed from where it starts (`TextColumn::make(`, or the story's `$table`)
     * so a method is credited to the class it is really called on.
     *
     * @param  list<ReflectionMethod>  $methods
     * @param  list<array{snippet: string}>  $stories
     * @param  list<string>  $imports
     * @return list<array{method: string, signature: string, description: string}>
     */
    protected static function api(array $methods, array $stories, array $imports): array
    {
        $aliases = [];

        foreach ($imports as $import) {
            [$class, $alias] = array_pad(preg_split('/\s+as\s+/i', $import), 2, null);
            $aliases[$alias ?? class_basename($class)] = $class;
        }

        $api = [];

        foreach ($methods as $i => $story) {
            $return = $story->getReturnType();
            $variable = ($story->getParameters()[0] ?? null)?->getName();
            $tokens = array_values(array_filter(
                \PhpToken::tokenize('<?php '.$stories[$i]['snippet']),
                fn (\PhpToken $token): bool => ! $token->isIgnorable(),
            ));

            // The class each nesting level's current chain is on; a new level starts at every ( and [.
            $chain = [$variable === null && $return instanceof ReflectionNamedType ? $return->getName() : null];

            foreach ($tokens as $t => $token) {
                $next = $tokens[$t + 1] ?? null;
                $class = $chain[array_key_last($chain)];

                if ($token->text === '(' || $token->text === '[') {
                    $chain[] = null;
                } elseif ($token->text === ')' || $token->text === ']') {
                    count($chain) > 1 && array_pop($chain);
                } elseif ($token->text === ',') {
                    $chain[array_key_last($chain)] = null;
                } elseif ($token->is(T_VARIABLE)) {
                    $chain[array_key_last($chain)] = $token->text === '$'.$variable && $return instanceof ReflectionNamedType ? $return->getName() : null;
                } elseif ($token->is(T_STRING) && $next?->is(T_DOUBLE_COLON) && ($tokens[$t + 3] ?? null)?->text === '(') {
                    $chain[array_key_last($chain)] = static::record($api, $aliases[$token->text] ?? null, $tokens[$t + 2]->text);
                } elseif ($token->is(T_OBJECT_OPERATOR) && $class !== null && $next?->is(T_STRING) && ($tokens[$t + 2] ?? null)?->text === '(') {
                    $chain[array_key_last($chain)] = static::record($api, $class, $next->text);
                }
            }
        }

        return array_values($api);
    }

    /**
     * Notes a method's signature and returns the class the chain continues on, or null when it leaves the fluent API.
     *
     * @param  array<string, array{method: string, signature: string, description: string}>  $api
     */
    protected static function record(array &$api, ?string $class, string $name): ?string
    {
        if ($class === null || ! method_exists($class, $name)) {
            return null;
        }

        $method = new ReflectionMethod($class, $name);
        $key = class_basename($class).'::'.$name;

        $api[$key] ??= [
            'method' => $key,
            'signature' => static::signature($method),
            'description' => (string) strtok(static::docblock($method), "\n"),
        ];

        $returns = (string) $method->getReturnType();

        return in_array($returns, ['static', 'self', '$this'], true) || is_a($returns, $class, true) ? $class : null;
    }

    public static function signature(ReflectionMethod $method): string
    {
        $parameters = array_map(function (ReflectionParameter $parameter): string {
            $default = '';

            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
                $default = ' = '.match (true) {
                    $parameter->isDefaultValueConstant() => $parameter->getDefaultValueConstantName(),
                    $value === null => 'null',
                    is_array($value) => $value === [] ? '[]' : '[...]',
                    is_bool($value) => $value ? 'true' : 'false',
                    is_string($value) => "'".addcslashes($value, "'\\")."'",
                    is_scalar($value) => (string) $value,
                    default => get_debug_type($value),
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

    public static function docblock(ReflectionMethod $story): string
    {
        $comment = (string) $story->getDocComment();

        return trim(preg_replace(['#^/\*\*|\*/$#', '#^\s*\* ?#m'], '', $comment));
    }
}
