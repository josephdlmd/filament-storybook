<?php

namespace Josephdlmd\FilamentStorybook\Stories\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Notifications\Notification;

/** Filament's forgotten-password page as the Storybook shows it: its button checks the email address and sends nothing. */
class RequestPasswordResetPreview extends RequestPasswordReset
{
    public function mount(): void
    {
        $this->form->fill();
    }

    public function request(): void
    {
        $this->form->getState();

        Notification::make()->title(__('filament-panels::auth/pages/password-reset/request-password-reset.notifications.sent.title'))->success()->send();
    }
}
