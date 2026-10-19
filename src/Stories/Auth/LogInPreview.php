<?php

namespace Josephdlmd\FilamentStorybook\Stories\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;

/**
 * Filament's log-in page as the Storybook shows it: it opens for a signed-in viewer, and its button checks the fields
 * without signing anyone in. Apps use Filament's own page (`->login()`); this class is the preview, never copied.
 */
class LogInPreview extends Login
{
    public function mount(): void
    {
        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
    {
        $this->form->getState();

        return null;
    }
}
