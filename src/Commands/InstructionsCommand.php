<?php

namespace Josephdlmd\FilamentStorybook\Commands;

use Illuminate\Console\Command;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Symfony\Component\Console\Output\OutputInterface;

class InstructionsCommand extends Command
{
    protected $signature = 'storybook:instructions';

    protected $description = 'How to write a Filament story, the guidance for every Filament component (which pattern or rule covers it), and every UI rule with its source (forms, feedback and dialogs, tables, layout, accessibility, sign-in). Run it before adding or changing a story, before using a Filament component no pattern shows, and when unsure how a screen should behave.';

    public function handle(): int
    {
        $this->output->writeln(file_get_contents(__DIR__.'/../../resources/instructions/stories.md')
            ."\n---\n\n".file_get_contents(__DIR__.'/../../docs/filament-coverage.md')
            ."\n---\n\n".file_get_contents(__DIR__.'/../../docs/rules.md')
            .implode('', array_map(fn (string $path): string => "\n---\n\n".file_get_contents($path), app(StoryRegistry::class)->rules())), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
