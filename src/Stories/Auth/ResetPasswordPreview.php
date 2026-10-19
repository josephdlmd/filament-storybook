<?php

namespace Josephdlmd\FilamentStorybook\Stories\Auth;

use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use SensitiveParameter;

/** Filament's reset-password page, opened from the email's link, as the Storybook shows it: it checks the new password and changes none. */
class ResetPasswordPreview extends ResetPassword
{
    public function mount(?string $email = null, #[SensitiveParameter] ?string $token = null): void
    {
        $this->form->fill(['email' => 'jane.cruz@example.com']);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $this->form->getState();

        return null;
    }
}
