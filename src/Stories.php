<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * One pattern's stories, like a Storybook CSF file: every public method declared on the subclass
 * is a story. It returns a configured component, or, for a whole screen, takes and returns the
 * Table or Schema a resource's table() or form() would.
 */
abstract class Stories
{
    /** Sidebar group and name, e.g. "Forms/Money input". */
    abstract public function title(): string;

    /** When to use this pattern and when not to. Agents read this before building UI. */
    public function description(): string
    {
        return '';
    }

    /**
     * Classes the pattern needs besides its stories, such as an importer; storybook-show prints their source to copy.
     *
     * @return list<class-string>
     */
    public function examples(): array
    {
        return [];
    }

    /**
     * Sample rows for column stories, keyed by column name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function records(): array
    {
        return [];
    }

    public function id(): string
    {
        return Str::slug(str_replace('/', ' ', $this->title()));
    }

    /**
     * @return array<string, ReflectionMethod>
     */
    public function stories(): array
    {
        return collect((new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method): bool => ! $method->isStatic()
                && ! $method->isConstructor()
                && ! method_exists(self::class, $method->getName()))
            ->keyBy(fn (ReflectionMethod $method): string => $method->getName())
            ->all();
    }

    /** Which canvas renders the story, read from its declared return type. */
    public static function kind(ReflectionMethod $story): ?string
    {
        $type = $story->getReturnType();
        $class = $type instanceof ReflectionNamedType ? $type->getName() : null;

        return match (true) {
            $class === null => null,
            is_a($class, Column::class, true) => 'column',
            is_a($class, Action::class, true), is_a($class, ActionGroup::class, true) => 'action',
            is_a($class, Component::class, true) => 'component',
            is_a($class, Table::class, true) => 'table',
            is_a($class, Schema::class, true) => 'schema',
            default => null,
        };
    }
}
