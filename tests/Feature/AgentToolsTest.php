<?php

use Josephdlmd\FilamentStorybook\Mcp\AuditPages;
use Josephdlmd\FilamentStorybook\Mcp\ListStories;
use Josephdlmd\FilamentStorybook\Mcp\ShowInstructions;
use Josephdlmd\FilamentStorybook\Mcp\ShowStories;
use Josephdlmd\FilamentStorybook\Mcp\TestStories;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Mcp\Request;

it('reports every story passing', function (): void {
    expect(testStoriesWithTheme())
        ->toStartWith('# 107 passing, 0 failing, 0 accessibility issues, 0 settled exceptions')
        ->toContain('- `forms-money-input.required`');
});

it('passes every check on the patterns taken from the accounting app', function (): void {
    expect(testStoriesWithTheme(['ids' => ['records-previous-and-next', 'records-key-figures', 'dashboards-key-figures-and-charts', 'forms-record-picker', 'reports-sales', 'reports-collections', 'reports-receivables-ageing']]))
        ->toStartWith('# 11 passing, 0 failing, 0 accessibility issues, 0 settled exceptions');
});

it('names every line-item field under Filament\'s own CSS, which hides the cell labels once the table shows headers', function (): void {
    expect(testStoriesWithTheme(['ids' => ['forms-line-items']]))
        ->toStartWith('# 2 passing, 0 failing, 0 accessibility issues, 0 settled exceptions');
});

it('reports a failing story with the line to fix, so the agent can self-correct', function (): void {
    app(StoryRegistry::class)->discover(__DIR__.'/../Fixtures/Stories', 'Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories');

    expect((string) (new TestStories)->handle(new Request(['ids' => ['broken-story']]))->content())
        ->toStartWith('# 16 passing, 33 failing, 1 accessibility issues, 0 settled exceptions')
        ->toContain('- `broken-story.vagueAndTitleCase` `story.shortcut-modifier`: a keyboard shortcut needs Ctrl, ⌘ or Alt')
        ->toContain('- `broken-story.collapsedFilters` `story.collapsed-filters`: AboveContentCollapsible puts the filter button alone')
        ->toContain('- `broken-story.works` `story.optional-label`: the field `name` is neither required() nor labelled "(optional)"')
        ->toContain('- `broken-story.nonNullableRecord` `story.nullable-record`: `fn (Model $record)` gets null while the table has no rows')
        ->toContain('- `broken-story.vagueAndTitleCase` `story.sample-content`: "Lorem ipsum" is placeholder content')
        ->toContain('- `broken-story.vagueAndTitleCase` `story.vague-label`: a button labelled "Submit"')
        ->toContain('- `broken-story.vagueAndTitleCase` `story.title-case`: "Save Order" is in Title Case')
        ->toContain('- `broken-story.vagueAndTitleCase` `story.notification-full-stop`: a notification title has no full stop')
        ->toContain('- `broken-story.unnamedIconButton`: [critical] button-name: Buttons must have discernible text')
        ->toContain('- `broken-story` `story.description`: add description()')
        ->toContain('- `broken-story.works` `story.placeholder`: the field `name` has a placeholder()')
        ->toContain('- `broken-story.floatMoney` `story.float-money`: round() works on floats')
        ->toContain('- `broken-story.ownSpacing` `story.own-spacing`: sets its own padding or margin')
        ->toContain('- `broken-story.ownTypeAndColour` `story.own-type`: sets its own font size, weight or colour')
        ->toContain('- `broken-story.hoverOnlyTooltip` `story.hover-tooltip`: a tooltip on a column, entry or hint icon shows on mouse hover only')
        ->toContain('- `broken-story.iconOnlyAction` `story.icon-tooltip`: an icon-only action needs a tooltip')
        ->toContain('- `broken-story.successCallout` `story.callout-status`: a callout is info or warning only')
        ->toContain('- `broken-story.nestedMenus` `story.nested-menus`: a menu opens inside another menu')
        ->toContain('- `broken-story.crampedChoices` `story.inline-options`: stack radio and checkbox options, without inline()')
        ->toContain('- `broken-story.openCollapsible` `story.collapsible-open`: a collapsible section starts collapsed')
        ->toContain('- `broken-story.crampedChoices` `story.wizard-step-name`: the wizard step "Choose the supplier now" is longer than 16 characters')
        ->toContain('- `broken-story.throws` `story.docblock`: add a one-line docblock')
        ->toContain('- `broken-story.failsWhileRendering`: Error: Call to a member function upper() on string at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:54')
        ->toContain('- `broken-story.throws`: RuntimeException: Story exploded at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:38')
        ->toContain('- `broken-story.untyped`: Not a story: return a Filament component, table column or action, or take and return a Table or Schema, at ')
        ->toContain('tests/Fixtures/Stories/BrokenStories.php:41')
        ->toContain('run storybook-test again until every story passes')
        ->toContain('- `broken-story.works`');

    expect((new TestStories)->handle(new Request(['ids' => ['nope']]))->isError())->toBeTrue();
});

it('checks each component\'s own options, so one tooltip or one collapsed section doesn\'t cover the others', function (): void {
    app(StoryRegistry::class)->discover(__DIR__.'/../Fixtures/Stories', 'Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories');

    expect((string) (new TestStories)->handle(new Request(['ids' => ['broken-story'], 'a11y' => false]))->content())
        ->toContain('- `broken-story.oneTooltipForTwo` `story.icon-tooltip`: an icon-only action needs a tooltip')
        ->toContain('- `broken-story.oneCollapsedForTwo` `story.collapsible-open`: a collapsible section starts collapsed')
        ->toContain('- `broken-story.oneCollapsedForTwo` `story.optional-label`: the field `billing_address` is neither required()')
        ->not->toContain('- `broken-story.oneCollapsedForTwo` `story.optional-label`: the field `address` is neither');
});

it('fails, saying how to enable the accessibility check, when it cannot run', function (): void {
    config(['filament-storybook.chromium' => '/nonexistent/chrome']);

    expect((string) (new TestStories)->handle(new Request(['ids' => ['forms-tin']]))->content())
        ->toStartWith('# 2 passing, 1 failing, 0 accessibility issues, 0 settled exceptions')
        ->toContain('- `accessibility` `a11y.unchecked`: Accessibility not checked (')
        ->toContain('npx playwright-core install chromium');
});

it('gives agents the story-writing rules', function (): void {
    expect((string) (new ShowInstructions)->handle(new Request)->content())
        ->toContain('# Writing Filament stories')
        ->toContain('## Choosing the component')
        ->toContain('## Fit the other patterns')
        ->toContain('Run `storybook-test`')
        ->toContain('# Filament coverage')
        ->toContain('| `KeyValue` |')
        ->toContain('## Business standards (from the owner)');
});

/** Agents look up each component in the coverage table, so a component Filament adds must get a row before it is used. */

it('lists every component Filament ships in the coverage table', function (): void {
    $coverage = file_get_contents(__DIR__.'/../../docs/filament-coverage.md');
    // Each folder's own level, and the folders that hold parts people build screens from; not Filament's internals (jobs, editor extensions).
    $dirs = ['forms/src/Components', 'infolists/src/Components', 'tables/src/Columns', 'tables/src/Filters', 'schemas/src/Components', 'actions/src',
        'tables/src/Columns/Layout', 'tables/src/Columns/Summarizers', 'tables/src/Filters/QueryBuilder/Constraints', 'schemas/src/Components/Tabs', 'schemas/src/Components/Wizard',
        'forms/src/Components/Repeater', 'forms/src/Components/Builder', 'infolists/src/Components/RepeatableEntry', 'filament/src/Clusters'];

    $missing = collect($dirs)
        ->flatMap(fn (string $dir): array => glob(__DIR__."/../../vendor/filament/{$dir}/*.php"))
        ->reject(fn (string $file): bool => (bool) preg_match('/^(abstract class|interface|trait) /m', file_get_contents($file)))
        ->map(fn (string $file): string => basename($file, '.php'))
        ->reject(fn (string $class): bool => str_contains($coverage, "`{$class}`"))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('joins the Boost MCP server when laravel/mcp is installed', function (): void {
    expect(config('boost.mcp.tools.include'))->toBe([ListStories::class, ShowStories::class, TestStories::class, ShowInstructions::class, AuditPages::class]);

    // A fatal error in any tool class takes down every tool in Boost's server, so each must load.
    expect(collect(config('boost.mcp.tools.include'))->map(fn (string $tool): string => (new $tool)->name())->all())
        ->toBe(['storybook-list', 'storybook-show', 'storybook-test', 'storybook-instructions', 'storybook-audit']);
});

it('reads the theme from Vite\'s dev server while `npm run dev` runs, then deletes its copy', function (): void {
    file_put_contents(public_path('hot'), 'http://dev.test:5173');
    $this->app->instance(Illuminate\Foundation\Vite::class, new Illuminate\Foundation\Vite);
    Josephdlmd\FilamentStorybook\Pages\Storybook::panel()->viteTheme('resources/css/theme.css');
    Illuminate\Support\Facades\Http::fake(['dev.test:5173/*' => Illuminate\Support\Facades\Http::response('.fi-btn { min-height: 2rem }')]);
    Illuminate\Support\Facades\Process::fake(['*' => Illuminate\Support\Facades\Process::result('{}')]);

    try {
        Josephdlmd\FilamentStorybook\Accessibility::check(['story' => '<p>Text</p>']);
    } finally {
        unlink(public_path('hot'));
    }

    // `?direct` gets the CSS itself; without it Vite answers with a script that loads it.
    Illuminate\Support\Facades\Http::assertSent(fn ($request): bool => $request->url() === 'http://dev.test:5173/resources/css/theme.css?direct');
    Illuminate\Support\Facades\Process::assertRan(function ($process): bool {
        $theme = collect(json_decode($process->input, true)['css'])->first(fn (string $path): bool => str_contains($path, 'fs-theme-'));

        return $theme !== null && ! file_exists($theme);
    });
});
