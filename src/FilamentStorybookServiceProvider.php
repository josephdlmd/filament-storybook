<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Josephdlmd\FilamentStorybook\Commands\InstallCommand;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp\AuditPages;
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
        $this->mergeConfigFrom(__DIR__.'/../config/filament-storybook.php', 'filament-storybook');

        // Shared patterns ship with the package; each app adds its own in app/Stories.
        $this->app->singleton(StoryRegistry::class, fn (): StoryRegistry => (new StoryRegistry)
            ->discover(__DIR__.'/Stories', __NAMESPACE__.'\\Stories')
            ->discover($this->app->path('Stories'), 'App\\Stories'));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-storybook');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            // The error pages (feedback-error-pages): each holds only its words and includes the package's layout.
            $this->publishes([__DIR__.'/../stubs/errors' => resource_path('views/errors')], 'filament-storybook-errors');
        }

        Livewire::component('filament-storybook.story-canvas', StoryCanvas::class);

        // Published by `php artisan filament:upgrade`, which Filament apps run after every composer install.
        FilamentAsset::register([
            Css::make('house', __DIR__.'/../resources/css/house.css'),
            Js::make('house', __DIR__.'/../resources/js/house.js'),
            Css::make('compact', __DIR__.'/../resources/css/compact.css'),
        ], 'josephdlmd/filament-storybook');

        // Boost is a dev dependency, so the tools join its MCP server only where it is installed.
        if (class_exists(Tool::class)) {
            config(['boost.mcp.tools.include' => [
                ...config('boost.mcp.tools.include', []),
                ListStories::class,
                ShowStories::class,
                TestStories::class,
                ShowInstructions::class,
                AuditPages::class,
            ]]);
        }
    }
}
