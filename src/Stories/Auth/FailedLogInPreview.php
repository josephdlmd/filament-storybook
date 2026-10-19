<?php

namespace Josephdlmd\FilamentStorybook\Stories\Auth;

/** The log-in page after a wrong email or password, with Filament's one message for both, as the house defaults word it. */
class FailedLogInPreview extends LogInPreview
{
    public function mount(): void
    {
        $this->form->fill(['email' => 'jane.cruz@example.com']);
        $this->addError('data.email', __('filament-panels::auth/pages/login.messages.failed'));
    }
}
