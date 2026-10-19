<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\File;
use Illuminate\View\Component as BladeComponent;
use ReflectionClass;
use ReflectionProperty;

/**
 * Custom components are package-only (docs/rules.md, Native Filament first): an app that needs one sends the owner an
 * extension proposal. An app's class is one when it wraps a Filament field, column, entry or layout component (a
 * `MoneyInput::make()` hides Filament's API and breaks across upgrades), gives a widget a Blade view of its own, or is a
 * Blade view component. Filament's stats, chart and table widgets configured in PHP, and Livewire pages, are not.
 */
class CustomComponents
{
    /**
     * The custom components among the classes under a directory, such as `app/`.
     *
     * @return list<class-string>
     */
    public static function in(string $directory): array
    {
        return collect(File::isDirectory($directory) ? File::allFiles($directory) : [])
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(function ($file): ?string {
                $source = $file->getContents();

                if (! preg_match('/^namespace\s+([^;]+);/m', $source, $namespace) || ! preg_match('/^(?:final\s+|abstract\s+|readonly\s+)*(?:class|trait)\s+(\w+)/m', $source, $class)) {
                    return null;
                }

                $name = "{$namespace[1]}\\{$class[1]}";

                return (class_exists($name) && static::isCustom($name)) || (str_contains("\\{$namespace[1]}\\", '\\Filament\\') && static::rendersOwnView($source)) ? $name : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * UI code (a class or trait under a `Filament` namespace) that draws a Blade view of the app's own, as `View::make('…')`,
     * `view('…')` or `->view('…')`, rather than a package's `name::view`: a component in all but name, such as a page header.
     */
    public static function rendersOwnView(string $source): bool
    {
        return (bool) preg_match('/\b(?:View::make|view)\(\s*[\'"](?![\w.-]+::)/', $source);
    }

    /** Why an agent should not ship it, for the tools' reports. */
    public static function message(string $class): string
    {
        return 'is a custom component ('.(is_a($class, BladeComponent::class, true) ? 'a Blade view component' : (is_a($class, Widget::class, true) ? 'a widget with its own Blade view' : (class_exists($class) && get_parent_class($class) && static::isCustom($class) ? 'it wraps Filament\'s `'.class_basename(get_parent_class($class)).'`' : 'it draws a Blade view of its own')))
            .'); custom components are package-only, so use Filament\'s own component configured as the pattern shows, or ask the owner for an extension proposal (docs/rules.md, Native Filament first).';
    }

    /** @param  class-string  $class */
    public static function isCustom(string $class): bool
    {
        if (is_a($class, BladeComponent::class, true)) {
            return true;
        }

        if (is_a($class, Widget::class, true)) {
            // A widget's `$view` declared by the class itself is a Blade view of its own; Filament's widgets declare theirs.
            return property_exists($class, 'view') && (new ReflectionProperty($class, 'view'))->getDeclaringClass()->getName() === $class;
        }

        return ! (new ReflectionClass($class))->isAbstract()
            && (is_a($class, Field::class, true) || is_a($class, Column::class, true) || is_a($class, Entry::class, true) || is_a($class, Component::class, true));
    }
}
