<?php

use Filament\Facades\Filament;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Boost\Mcp\ToolRegistry;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Storybook::panel());
});

it('renders every stories pattern', function () {
    foreach (app(StoryRegistry::class)->all() as $stories) {
        foreach (array_keys($stories->stories()) as $story) {
            Livewire::test(StoryCanvas::class, ['stories' => $stories::class, 'story' => $story])->assertOk();
        }
    }
});

// The tools reach Boost through boost.mcp.tools.include, which isn't in Boost's config file; fail loudly if an update drops it.
it('gives agents the storybook tools through Boost', function () {
    expect(ToolRegistry::getAvailableTools())->toContain(
        Mcp\ListStories::class,
        Mcp\ShowStories::class,
        Mcp\ShowInstructions::class,
        Mcp\TestStories::class,
        Mcp\AuditPages::class,
    );
});
