<?php

namespace Josephdlmd\FilamentStorybook;

use Illuminate\Support\ServiceProvider;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp\ListStories;
use Josephdlmd\FilamentStorybook\Mcp\ShowInstructions;
use Josephdlmd\FilamentStorybook\Mcp\ShowStories;
use Josephdlmd\FilamentStorybook\Mcp\TestStories;
use Laravel\Mcp\Server\Tool;
use Livewire\Livewire;

class FilamentStorybookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Shared patterns ship with the package; each app adds its own in app/Stories.
        $this->app->singleton(StoryRegistry::class, fn (): StoryRegistry => (new StoryRegistry)
            ->discover(__DIR__.'/Stories', __NAMESPACE__.'\\Stories')
            ->discover($this->app->path('Stories'), 'App\\Stories'));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-storybook');

        Livewire::component('filament-storybook.story-canvas', StoryCanvas::class);

        // Boost is a dev dependency, so the tools join its MCP server only where it is installed.
        if (class_exists(Tool::class)) {
            config(['boost.mcp.tools.include' => [
                ...config('boost.mcp.tools.include', []),
                ListStories::class,
                ShowStories::class,
                TestStories::class,
                ShowInstructions::class,
            ]]);
        }
    }
}
