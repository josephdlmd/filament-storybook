<?php

use Josephdlmd\FilamentStorybook\Commands\InstallCommand;

it('installs into an app that meets the standard, and changing nothing on a second run', function (): void {
    $provider = "<?php\n\nnamespace App\\Providers\\Filament;\n\nuse Filament\\Panel;\n\nclass AdminPanelProvider\n{\n    public function panel(Panel \$panel): Panel\n    {\n        return \$panel\n            ->default()\n            ->id('admin')\n            ->path('admin');\n    }\n}\n";
    $installed = InstallCommand::addPlugin($provider);

    expect($installed)
        ->toContain("            ->id('admin')\n            ->plugin(\\Josephdlmd\\FilamentStorybook\\StorybookPlugin::make())\n            ->path('admin');")
        ->and(InstallCommand::addPlugin($installed))->toBe($installed);

    $boost = InstallCommand::addBoostPackage('{"agents": ["claude_code"], "packages": ["filament/filament"]}');
    expect(json_decode($boost, true)['packages'])->toBe(['filament/filament', 'josephdlmd/filament-storybook'])
        ->and(InstallCommand::addBoostPackage($boost))->toBe($boost)
        ->and(InstallCommand::addGitignoreLine("/vendor\n"))->toBe("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n")
        ->and(InstallCommand::addGitignoreLine("/vendor\n/public/css/josephdlmd\n"))->toBe("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n")
        ->and(InstallCommand::addGitignoreLine("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n"))->toBe("/vendor\n/public/css/josephdlmd\n/public/js/josephdlmd\n");

    $settings = InstallCommand::addAgentCommands('{"permissions": {"allow": ["Bash(composer check)"]}, "model": "opus"}');
    expect(json_decode($settings, true))->toBe(['permissions' => ['allow' => ['Bash(composer check)', 'Bash(php artisan storybook:*)']], 'model' => 'opus'])
        ->and(InstallCommand::addAgentCommands($settings))->toBe($settings)
        ->and(json_decode(InstallCommand::addAgentCommands('{}'), true))->toBe(['permissions' => ['allow' => ['Bash(php artisan storybook:*)']]]);
});

it('refuses an app that does not meet the standard, saying what to change', function (): void {
    expect(InstallCommand::unmetRequirements(boost: true, pest: true, npm: true, defaultPanel: true))->toBe([])
        ->and(InstallCommand::unmetRequirements(boost: true, pest: false, npm: true, defaultPanel: false))->toHaveCount(2)
        ->and(implode(' ', InstallCommand::unmetRequirements(boost: false, pest: false, npm: false, defaultPanel: false)))
        ->toContain('Laravel Boost')->toContain('Pest')->toContain('package.json')->toContain('default Filament panel');

    $this->artisan('storybook:install')->assertExitCode(1);
});

it('refuses a boost.json or .claude/settings.json that isn\'t valid JSON, rather than rewriting it with only this package', function (): void {
    expect(implode(' ', InstallCommand::unmetRequirements(boost: true, pest: true, npm: true, defaultPanel: true, boostJson: false)))
        ->toContain('Fix boost.json: it isn\'t valid JSON');

    expect(implode(' ', InstallCommand::unmetRequirements(boost: true, pest: true, npm: true, defaultPanel: true, claudeSettings: false)))
        ->toContain('Fix .claude/settings.json: it isn\'t valid JSON');
});

it('refuses an app without Filament\'s Compact theme, which the locked look is built on', function (): void {
    expect(implode(' ', InstallCommand::unmetRequirements(boost: true, pest: true, npm: true, defaultPanel: true, compactTheme: false)))
        ->toContain('composer require filament/compact-theme');
});
