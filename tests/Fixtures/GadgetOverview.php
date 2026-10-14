<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Pages\Page;

/** A panel page of the app's own that, opened without a period, sends the user back to itself with their last one. */
class GadgetOverview extends Page
{
    public ?string $period = null;

    public function mount(): void
    {
        if (request()->query('period') === null) {
            $this->redirect(static::getUrl(['period' => 'month']));

            return;
        }

        $this->period = request()->query('period');
    }
}
