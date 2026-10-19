<?php

namespace Josephdlmd\FilamentStorybook\Stories\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;

/**
 * The log-in page's second step, asking for the code from the user's authenticator app, for a sample user who has one.
 * It shows the code step of every app-authentication provider the panel sets (`->multiFactorAuthentication([...])`).
 */
class TwoFactorPreview extends LogInPreview
{
    public function mount(): void
    {
        parent::mount();
        // Any value shows the second step; the sample user below stands in for the one who typed their password.
        $this->userUndertakingMultiFactorAuthentication = 'preview';
    }

    public function authenticate(): ?LoginResponse
    {
        $this->multiFactorChallengeForm->getState();

        return null;
    }

    protected function getUserUndertakingMultiFactorAuthentication(): ?Authenticatable
    {
        return new class extends User implements HasAppAuthentication
        {
            protected $attributes = ['id' => 1, 'name' => 'Jane Cruz', 'email' => 'jane.cruz@example.com'];

            public function getAppAuthenticationSecret(): ?string
            {
                return 'PREVIEWSECRETKEY';
            }

            public function saveAppAuthenticationSecret(?string $secret): void {}

            public function getAppAuthenticationHolderName(): string
            {
                return $this->email;
            }
        };
    }
}
