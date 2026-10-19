<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Mcp;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Mcp\Request;
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

// Strict mode: every finding fails, accessibility included, unless the owner settled it in config/storybook-exceptions.php.
// The report is the failure message, so it says what to fix.
it('passes storybook:test', function () {
    expect((string) (new Mcp\TestStories)->handle(new Request)->content())->toContain(' 0 failing, 0 accessibility issues');
});

it('passes storybook-audit on every panel', function () {
    // The app's own users, so one may open each panel; every policy allows, so the audit opens every page; and one record
    // per resource from its model's factory, so the pages of one record open too.
    $this->seed();
    Gate::before(fn (): bool => true);

    foreach (Filament::getPanels() as $panel) {
        foreach ($panel->getResources() as $resource) {
            $model = $resource::getModel();

            if (method_exists($model, 'factory') && $model::query()->doesntExist()) {
                $model::factory()->create();
            }
        }
    }

    foreach (Filament::getPanels() as $panel) {
        expect((string) (new Mcp\AuditPages)->handle(new Request(['panel' => $panel->getId()]))->content())->toContain(' 0 failing, 0 accessibility issues');
    }
});
