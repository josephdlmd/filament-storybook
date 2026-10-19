<?php

namespace Josephdlmd\FilamentStorybook\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    protected $signature = 'storybook:list';

    protected $description = 'List this app\'s approved Filament UI patterns (stories). Run it before building any Filament form, table, infolist or action, and reuse a matching pattern through storybook:show instead of writing your own.';

    public function handle(): int
    {
        $lines = collect(app(StoryRegistry::class)->all())
            ->map(fn (Stories $stories, string $id): string => "- `{$id}` {$stories->title()}: ".(Str::before((string) strtok($stories->description(), "\n"), ' Basis:') ?: 'no description')
                .' (stories: '.implode(', ', array_keys($stories->stories())).')')
            ->values();

        $this->output->writeln($lines->isEmpty()
            ? 'No stories yet. Add a class extending '.Stories::class.' to app/Stories.'
            : $lines->implode("\n"), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
