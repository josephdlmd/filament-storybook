<?php

use Josephdlmd\FilamentStorybook\Mcp\TestStories;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Josephdlmd\FilamentStorybook\Tests\TestCase;
use Laravel\Mcp\Request;

uses(TestCase::class)
    ->beforeEach(function (): void {
        $this->actingAs(User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => 'secret']));
    })
    ->in('Feature');

/** Runs storybook-test under Filament's compiled theme, which apps load; the tests have no published one, and unstyled buttons fail WCAG 2.2's target size. */
function testStoriesWithTheme(array $arguments = []): string
{
    $theme = public_path('css/filament/filament/app.css');
    @mkdir(dirname($theme), recursive: true);
    copy(__DIR__.'/../vendor/filament/filament/dist/theme.css', $theme);

    try {
        return (string) (new TestStories)->handle(new Request($arguments))->content();
    } finally {
        unlink($theme);
    }
}
