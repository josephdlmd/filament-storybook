<?php

namespace Josephdlmd\FilamentStorybook\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Connects an app that meets the standard. An app that doesn't is told what to change, and nothing is touched.
 * Every edit is idempotent, so it is safe to run again.
 */
class InstallCommand extends Command
{
    protected $signature = 'storybook:install';

    protected $description = 'Connect Filament Storybook: plugin, Boost guideline and tools, tests, accessibility checks';

    public function handle(): int
    {
        $provider = collect(File::exists(app_path('Providers/Filament')) ? File::files(app_path('Providers/Filament')) : [])
            ->map->getPathname()
            ->first(fn (string $path): bool => str_contains(File::get($path), '->default()'));

        $boostJson = File::exists(base_path('boost.json')) ? File::get(base_path('boost.json')) : '{}';

        $unmet = static::unmetRequirements(
            boost: class_exists(\Laravel\Boost\Mcp\ToolRegistry::class),
            pest: class_exists(\Pest\TestSuite::class),
            npm: File::exists(base_path('package.json')),
            defaultPanel: $provider !== null && preg_match('/^\s*->id\([^)]*\)$/m', File::get($provider)) === 1,
            // Rewriting a file that doesn't decode would keep only this package.
            boostJson: blank($boostJson) || json_validate($boostJson),
            compactTheme: class_exists(\Filament\CompactTheme\CompactThemePlugin::class),
        );

        if ($unmet !== []) {
            $this->components->error('This app does not meet the Filament Storybook standard yet. Change it, then run this again:');
            $this->components->bulletList($unmet);

            return self::FAILURE;
        }

        File::put($provider, static::addPlugin(File::get($provider)));
        Process::path(base_path())->run(['vendor/bin/pint', '--quiet', $provider]);
        $this->components->task('Storybook plugin on the default panel');

        File::put(base_path('boost.json'), static::addBoostPackage($boostJson));
        $this->components->task('Boost guideline and tools', fn (): bool => $this->callSilently('boost:update') === 0);

        File::ensureDirectoryExists(base_path('tests/Feature'));
        File::copy(__DIR__.'/../../stubs/StoriesTest.php', base_path('tests/Feature/StoriesTest.php'));
        $this->components->task('tests/Feature/StoriesTest.php');

        File::put(base_path('.gitignore'), static::addGitignoreLine(File::exists(base_path('.gitignore')) ? File::get(base_path('.gitignore')) : ''));
        $this->callSilently('filament:assets');
        $this->components->task('House CSS published and ignored by git');

        $this->components->task('Accessibility check packages', fn (): bool => Process::path(base_path())->timeout(300)
            ->run(['npm', 'install', '--save-dev', '--no-audit', '--no-fund', 'playwright-core@1.58.2', 'axe-core@4.11.0'])->successful());

        $this->newLine();
        $this->components->info('Connected. Next:');
        $this->components->bulletList([
            'Once per machine: npx playwright-core install chromium',
            'Make each panel\'s theme.css the package theme plus its @source lines: @import \'../../../../vendor/josephdlmd/filament-storybook/resources/css/theme.css\'; then npm run build.',
            'Run the tests. Required-field errors now read "Enter name" and delete buttons are outlined; update tests that expect Laravel\'s "The name field is required."',
            'Restart your coding agent and ask it to run storybook-audit; fix what it reports.',
        ]);

        return self::SUCCESS;
    }

    /**
     * The standard every app meets: the filament-starter template's stack.
     *
     * @return list<string>
     */
    public static function unmetRequirements(bool $boost, bool $pest, bool $npm, bool $defaultPanel, bool $boostJson = true, bool $compactTheme = true): array
    {
        return array_values(array_filter([
            $boost ? null : 'Laravel Boost: composer require laravel/boost --dev, then php artisan boost:install',
            $pest ? null : 'Pest: composer require pestphp/pest pestphp/pest-plugin-laravel --dev',
            $npm ? null : 'npm: a package.json at the project root (the Vite setup Laravel ships with)',
            $defaultPanel ? null : 'A default Filament panel in app/Providers/Filament with ->default() and its ->id(...) on a line of its own',
            $boostJson ? null : 'Fix boost.json: it isn\'t valid JSON',
            $compactTheme ? null : 'Filament\'s Compact theme, which the look is built on: buy a licence, then composer require filament/compact-theme through Filament\'s Composer repository',
        ]));
    }

    public static function addPlugin(string $source): string
    {
        if (str_contains($source, 'StorybookPlugin')) {
            return $source;
        }

        // The full class name, so the line works without an import that a second edit could miss.
        return (string) preg_replace('/^(\s*)(->id\([^)]*\))$/m', "$1$2\n$1->plugin(\\Josephdlmd\\FilamentStorybook\\StorybookPlugin::make())", $source, 1);
    }

    public static function addBoostPackage(string $json): string
    {
        $config = json_decode($json, true) ?: [];
        $config['packages'] = array_values(array_unique([...($config['packages'] ?? []), 'josephdlmd/filament-storybook']));

        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    /** The package's published CSS and script are build output, like Filament's own. */
    public static function addGitignoreLine(string $gitignore): string
    {
        foreach (['/public/css/josephdlmd', '/public/js/josephdlmd'] as $line) {
            if (! str_contains($gitignore, $line)) {
                $gitignore = rtrim($gitignore, "\n").($gitignore === '' ? '' : "\n")."{$line}\n";
            }
        }

        return $gitignore;
    }
}
