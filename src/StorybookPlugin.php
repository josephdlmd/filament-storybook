<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Josephdlmd\FilamentStorybook\Pages\Storybook;

class StorybookPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-storybook';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Storybook::class]);
    }

    public function boot(Panel $panel): void {}
}
