<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Attributes\Record;
use Josephdlmd\FilamentStorybook\Attributes\Section;
use Josephdlmd\FilamentStorybook\Attributes\States;
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
     * Classes the pattern needs besides its stories, such as an importer; storybook:show prints their source to copy.
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

    /**
     * The model of the canvas's sample record, for a story whose fields use its relationships.
     *
     * @return class-string<Model>|null
     */
    public function model(): ?string
    {
        return null;
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
            is_a($class, SimplePage::class, true) => 'page',
            is_a($class, Table::class, true) => 'table',
            is_a($class, Schema::class, true) => 'schema',
            default => null,
        };
    }

    /** The five conditions a screen is shown in (GLOSSARY.md, State). */
    public const STATES = ['populated', 'loading', 'empty', 'error', 'forbidden'];

    /**
     * The states a story shows: populated, and those its #[States] adds.
     *
     * @return list<string>
     */
    public static function states(ReflectionMethod $story): array
    {
        $declared = ($story->getAttributes(States::class)[0] ?? null)?->newInstance()->states ?? [];

        return array_values(array_unique(['populated', ...$declared]));
    }

    /**
     * The states a story owes: a table or schema story is a screen and owes all five, a #[Record] screen all but empty, and a
     * #[Section] only those it declares; a guest page such as log in (a Filament simple page) loads no data and forbids no
     * one, so it owes populated and error; fields, columns and actions owe none.
     *
     * @return list<string>
     */
    public static function owedStates(ReflectionMethod $story): array
    {
        return match (true) {
            ! in_array(static::kind($story), ['table', 'schema', 'page'], true), $story->getAttributes(Section::class) !== [] => [],
            static::kind($story) === 'page' => ['populated', 'error'],
            $story->getAttributes(Record::class) !== [] => array_values(array_diff(static::STATES, ['empty'])),
            default => static::STATES,
        };
    }
}
